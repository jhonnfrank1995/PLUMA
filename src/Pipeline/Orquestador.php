<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use Pluma\Datos\CandadoGlobalInterface;
use Pluma\Datos\RepositorioBitacoraInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Investigacion\InvestigadorInterface;
use Pluma\Kernel\RelojInterface;
use Pluma\Proveedores\ProveedorTendenciasException;
use Pluma\Publicacion\CreadorBorradorInterface;
use Pluma\Redaccion\RedactorInterface;
use Pluma\Sensores\SensorInterface;
use Throwable;

/**
 * "Cada vez que me ejecuto, cumplo mi cuota del día" (Libro Cap. 9.1) — en
 * Etapa 1, sin compuertas ni cuota todavía: detecta, avanza el pipeline
 * hasta REDACTADA y crea el borrador WP. Presupuesto de tiempo con corte
 * limpio entre lotes (Cap. 9.4): mejor varias ejecuciones cortas que una
 * larga que muere por timeout.
 */
final class Orquestador {

	private const LIMITE_POR_LOTE = 5;

	public function __construct(
		private readonly CandadoGlobalInterface $candado,
		private readonly RepositorioBitacoraInterface $bitacora,
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioTendenciasInterface $tendencias,
		private readonly Transicionador $transicionador,
		private readonly SensorInterface $sensor,
		private readonly InvestigadorInterface $investigador,
		private readonly RedactorInterface $redactor,
		private readonly CreadorBorradorInterface $creadorBorrador,
		private readonly RelojInterface $reloj,
	) {
	}

	/**
	 * @return array{ejecutado: bool, lotesProcesados: int, errores: list<string>}
	 */
	public function ejecutarTick( int $presupuestoSegundos = 90 ): array {
		if ( ! $this->candado->adquirir() ) {
			$this->bitacora->finalizarEjecucion( $this->bitacora->iniciarEjecucion( $this->reloj->ahora() ), $this->reloj->ahora(), 0, array( 'candado ocupado: otra ejecución en curso' ) );

			return array(
				'ejecutado'       => false,
				'lotesProcesados' => 0,
				'errores'         => array(),
			);
		}

		$inicio          = microtime( true );
		$bitacoraId      = $this->bitacora->iniciarEjecucion( $this->reloj->ahora() );
		$errores         = array();
		$lotesProcesados = 0;

		try {
			$errores = array( ...$errores, ...$this->detectarTendencias() );

			[$avanzados, $erroresPipeline] = $this->avanzarPipeline( $inicio, $presupuestoSegundos );
			$lotesProcesados               = $avanzados;
			$errores                       = array( ...$errores, ...$erroresPipeline );
		} finally {
			$this->bitacora->finalizarEjecucion( $bitacoraId, $this->reloj->ahora(), $lotesProcesados, $errores );
			$this->candado->liberar();
		}

		return array(
			'ejecutado'       => true,
			'lotesProcesados' => $lotesProcesados,
			'errores'         => $errores,
		);
	}

	/**
	 * @return list<string>
	 */
	private function detectarTendencias(): array {
		try {
			$detectadas = $this->sensor->detectar();
		} catch ( ProveedorTendenciasException $e ) {
			// El Sensor caído no detiene el resto del tick (pl-proveedor-ia
			// §4): se registra y el pipeline sigue avanzando lo que ya tiene.
			return array( 'sensor ' . $this->sensor->nombre() . ': ' . $e->getMessage() );
		}

		foreach ( $detectadas as $detectada ) {
			if ( $this->tendencias->existePorTermino( $detectada->termino, $detectada->fuenteSenal ) ) {
				continue;
			}

			$tendenciaId = $this->tendencias->guardar( $detectada, $this->reloj->ahora() );
			$this->piezas->crear( $tendenciaId, $this->reloj->ahora() );
		}

		return array();
	}

	/**
	 * @return array{0: int, 1: list<string>}
	 */
	private function avanzarPipeline( float $inicio, int $presupuestoSegundos ): array {
		$procesadas = 0;
		$errores    = array();

		foreach ( $this->piezas->obtenerPorEstado( EstadoPieza::Detectada, self::LIMITE_POR_LOTE ) as $pieza ) {
			if ( $this->presupuestoAgotado( $inicio, $presupuestoSegundos ) ) {
				return array( $procesadas, $errores );
			}

			$this->procesarInvestigacion( $pieza, $errores );
			++$procesadas;
		}

		foreach ( $this->piezas->obtenerPorEstado( EstadoPieza::Investigada, self::LIMITE_POR_LOTE ) as $pieza ) {
			if ( $this->presupuestoAgotado( $inicio, $presupuestoSegundos ) ) {
				return array( $procesadas, $errores );
			}

			$this->procesarRedaccionYBorrador( $pieza, $errores );
			++$procesadas;
		}

		return array( $procesadas, $errores );
	}

	private function presupuestoAgotado( float $inicio, int $presupuestoSegundos ): bool {
		return ( microtime( true ) - $inicio ) >= $presupuestoSegundos;
	}

	/**
	 * @param list<string> $errores
	 */
	private function procesarInvestigacion( Pieza $pieza, array &$errores ): void {
		try {
			$transitada = $this->transicionador->transitar( $pieza->id, EstadoPieza::EnInvestigacion, 'inicio de investigación' );

			if ( null === $transitada ) {
				return;
			}

			$datosTendencia = $this->tendencias->obtenerPorId( $pieza->tendenciaId );

			if ( null === $datosTendencia ) {
				throw new PiezaNoEncontradaException( $pieza->tendenciaId );
			}

			$expediente = $this->investigador->investigar(
				$datosTendencia['termino'],
				$datosTendencia['articulosRelacionados']
			);

			$this->piezas->actualizarExpediente( $pieza->id, $expediente, $this->reloj->ahora() );
			$this->transicionador->transitar( $pieza->id, EstadoPieza::Investigada, 'expediente construido' );
		} catch ( Throwable $e ) {
			$this->marcarFallida( $pieza->id, $e, $errores );
		}
	}

	/**
	 * @param list<string> $errores
	 */
	private function procesarRedaccionYBorrador( Pieza $pieza, array &$errores ): void {
		try {
			$transitada = $this->transicionador->transitar( $pieza->id, EstadoPieza::EnRedaccion, 'inicio de redacción' );

			if ( null === $transitada || null === $transitada->expediente ) {
				return;
			}

			$resultado = $this->redactor->redactar( $transitada );

			if ( $resultado->retenida ) {
				// El Corrector Interno no aprobó tras el máximo de ciclos (Libro
				// Cap. 5.6): revisión humana, no un fallo del sistema.
				$this->transicionador->transitar(
					$pieza->id,
					EstadoPieza::Retenida,
					$resultado->motivoRetenida ?? 'El Corrector Interno no aprobó la pieza.'
				);

				return;
			}

			$this->transicionador->transitar( $pieza->id, EstadoPieza::Redactada, 'borrador construido' );

			$postId = $this->creadorBorrador->crear( $resultado->titulo, $resultado->cuerpoHtml );
			$this->piezas->actualizarPostId( $pieza->id, $postId, $this->reloj->ahora() );
		} catch ( Throwable $e ) {
			$this->marcarFallida( $pieza->id, $e, $errores );
		}
	}

	/**
	 * @param list<string> $errores
	 */
	private function marcarFallida( int $piezaId, Throwable $error, array &$errores ): void {
		$errores[] = "pieza {$piezaId}: " . $error->getMessage();

		try {
			$this->transicionador->transitar( $piezaId, EstadoPieza::Fallida, $error->getMessage() );
		} catch ( Throwable $errorSecundario ) {
			// Si ni siquiera se puede marcar como Fallida (p. ej. la Pieza ya
			// no existe), el error ya quedó registrado en la bitácora arriba;
			// se añade este segundo motivo para no perder la pista.
			$errores[] = "pieza {$piezaId} (al marcar fallida): " . $errorSecundario->getMessage();
		}
	}
}
