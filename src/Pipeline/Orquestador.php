<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use Pluma\Compuertas\EvaluadorCompuertas;
use Pluma\Compuertas\ModoOperacion;
use Pluma\Datos\CandadoGlobalInterface;
use Pluma\Datos\RepositorioBitacoraInterface;
use Pluma\Datos\RepositorioBorradoresInterface;
use Pluma\Datos\RepositorioColaPublicacionInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Investigacion\InvestigadorInterface;
use Pluma\Kernel\RelojInterface;
use Pluma\Proveedores\ProveedorTendenciasException;
use Pluma\Publicacion\CreadorBorradorInterface;
use Pluma\Publicacion\PublicadorInterface;
use Pluma\Redaccion\RedactorInterface;
use Pluma\Sensores\ComparadorHistorias;
use Pluma\Sensores\RelacionHistoria;
use Pluma\Sensores\SensorInterface;
use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\MotorSeo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Taxonomia\ResultadoTaxonomia;
use Pluma\Taxonomia\Taxonomo;
use Throwable;

/**
 * "Cada vez que me ejecuto, cumplo mi cuota del día" (Libro Cap. 9.1):
 * candado global, avance de todo el pipeline (RADAR → INVESTIGADOR → SALA
 * DE REDACCIÓN → MOTOR SEO → TAXÓNOMO → COMPUERTAS → PUBLICADOR) con
 * presupuesto de tiempo y corte limpio entre lotes (Cap. 9.4), cadencia
 * real con cuota elástica/ventanas/jitter/topes (Cap. 9.2-9.3), modos reales
 * con ventana de veto en Copiloto (Cap. 2.4), y escasez honesta (Cap. 9.3
 * punto 6: nunca se rebajan umbrales para rellenar la cuota).
 */
final class Orquestador {

	private const LIMITE_POR_LOTE                    = 5;
	private const DIAS_VENTANA_COMPARACION_HISTORIAS = 14;
	private const LIMITE_CANDIDATAS_COMPARACION      = 20;

	public const OPCION_MODO_OPERACION     = 'pluma_modo_operacion';
	public const OPCION_VENTANA_VETO_HORAS = 'pluma_ventana_veto_horas';

	private const MODO_OPERACION_DEFECTO     = 'copiloto';
	private const VENTANA_VETO_HORAS_DEFECTO = 2;
	private const PIEZAS_PROPIAS_RECIENTES   = 20;

	public function __construct(
		private readonly CandadoGlobalInterface $candado,
		private readonly RepositorioBitacoraInterface $bitacora,
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioTendenciasInterface $tendencias,
		private readonly RepositorioBorradoresInterface $borradores,
		private readonly RepositorioColaPublicacionInterface $colaPublicacion,
		private readonly Transicionador $transicionador,
		private readonly SensorInterface $sensor,
		private readonly InvestigadorInterface $investigador,
		private readonly RedactorInterface $redactor,
		private readonly MotorSeo $motorSeo,
		private readonly Taxonomo $taxonomo,
		private readonly EvaluadorCompuertas $evaluadorCompuertas,
		private readonly LectorConfiguracionCadencia $lectorCadencia,
		private readonly ProgramadorCadencia $programadorCadencia,
		private readonly CreadorBorradorInterface $creadorBorrador,
		private readonly PublicadorInterface $publicador,
		private readonly ComparadorHistorias $comparadorHistorias,
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

			$this->procesarPublicacionesVencidas( $errores );
			$this->verificarEscasezHonesta( $errores );
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

			$candidatas = $this->tendencias->obtenerRecientesConPiezaViva(
				self::DIAS_VENTANA_COMPARACION_HISTORIAS,
				self::LIMITE_CANDIDATAS_COMPARACION,
				$this->reloj->ahora()
			);
			$resultado  = $this->comparadorHistorias->comparar( $detectada, $candidatas );

			if ( RelacionHistoria::Identica === $resultado->relacion ) {
				// Ya cubierta bajo otro titular (huella semántica, Libro Cap.
				// 3.4) — extiende la deduplicación exacta de arriba, no duplica.
				continue;
			}

			if ( RelacionHistoria::Evoluciona === $resultado->relacion && null !== $resultado->tendenciaRelacionadaId ) {
				// "Dos golpes": NO se crea Pieza automáticamente — el editor
				// confirma desde la Sala de Tendencias (decisión del
				// propietario, 2026-07-23).
				$this->tendencias->guardarComoPosibleActualizacion( $detectada, $resultado->tendenciaRelacionadaId, $this->reloj->ahora() );
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

		foreach ( $this->piezas->obtenerPorEstado( EstadoPieza::Redactada, self::LIMITE_POR_LOTE ) as $pieza ) {
			if ( $this->presupuestoAgotado( $inicio, $presupuestoSegundos ) ) {
				return array( $procesadas, $errores );
			}

			$this->procesarOptimizacion( $pieza, $errores );
			++$procesadas;
		}

		foreach ( $this->piezas->obtenerPorEstado( EstadoPieza::Optimizada, self::LIMITE_POR_LOTE ) as $pieza ) {
			if ( $this->presupuestoAgotado( $inicio, $presupuestoSegundos ) ) {
				return array( $procesadas, $errores );
			}

			$this->procesarCompuertas( $pieza, $errores );
			++$procesadas;
		}

		foreach ( $this->piezas->obtenerPorEstado( EstadoPieza::Aprobada, self::LIMITE_POR_LOTE ) as $pieza ) {
			if ( $this->presupuestoAgotado( $inicio, $presupuestoSegundos ) ) {
				return array( $procesadas, $errores );
			}

			$this->procesarProgramacion( $pieza, $errores );
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
	 * Redactada → Optimizada (Libro Cap. 6-7): Motor SEO + Taxónomo. Sin
	 * Ficha de Decisión Editorial (redacción mecánica de respaldo, deuda
	 * PLUMA-E2-1) no hay clasificación/tesis con qué optimizar — la pieza
	 * avanza igual a Optimizada, sin datos SEO/taxonomía, para no bloquear
	 * el pipeline; la Compuerta de Calidad la retendrá para revisión humana
	 * en el siguiente paso por falta de Borrador con anotaciones del
	 * Corrector Interno.
	 *
	 * @param list<string> $errores
	 */
	private function procesarOptimizacion( Pieza $pieza, array &$errores ): void {
		try {
			$transitada = $this->transicionador->transitar( $pieza->id, EstadoPieza::Optimizada, 'optimización SEO y taxonomía' );

			if ( null === $transitada || null === $transitada->expediente || null === $transitada->fichaDecisionEditorial ) {
				return;
			}

			$ficha = $transitada->fichaDecisionEditorial;
			$post  = null !== $transitada->postId ? get_post( $transitada->postId ) : null;

			$datosSeo = $this->motorSeo->optimizar(
				$pieza->id,
				$transitada->expediente,
				$ficha,
				null !== $post ? $post->post_title : ''
			);
			$this->piezas->actualizarDatosSeo( $pieza->id, $datosSeo, $this->reloj->ahora() );

			$resultadoTaxonomia = $this->taxonomo->clasificar( $transitada->expediente, $ficha->clasificacion->tema, $ficha->tesisElegida()->tesis );
			$this->piezas->actualizarResultadoTaxonomia( $pieza->id, $resultadoTaxonomia, $this->reloj->ahora() );
		} catch ( Throwable $e ) {
			$this->marcarFallida( $pieza->id, $e, $errores );
		}
	}

	/**
	 * Optimizada → EnRevision → Aprobada/Retenida (Libro Cap. 8): único
	 * camino legal hacia Aprobada. Piloto nunca auto-aprueba (Cap. 2.4): la
	 * pieza queda en EnRevision para acción humana explícita en la Sala de
	 * Revisión.
	 *
	 * @param list<string> $errores
	 */
	private function procesarCompuertas( Pieza $pieza, array &$errores ): void {
		try {
			$transitada = $this->transicionador->transitar( $pieza->id, EstadoPieza::EnRevision, 'evaluación de compuertas' );

			if ( null === $transitada || null === $transitada->expediente || null === $transitada->fichaDecisionEditorial ) {
				return;
			}

			$ultimoBorrador = $this->borradores->obtenerUltimo( $pieza->id );

			if ( null === $ultimoBorrador ) {
				// Redacción mecánica de respaldo (deuda PLUMA-E2-1): sin
				// anotaciones del Corrector Interno no hay con qué evaluar
				// Calidad — se retiene para revisión humana, nunca se aprueba a ciegas.
				return;
			}

			$ficha      = $transitada->fichaDecisionEditorial;
			$modoGlobal = $this->modoGlobalConfigurado();

			$resultado = $this->evaluadorCompuertas->evaluar(
				$transitada->expediente,
				$ficha->clasificacion,
				$ficha->esqueleto,
				$ultimoBorrador,
				$ultimoBorrador->contenido,
				true,
				$this->textosPropiosRecientes(),
				$modoGlobal
			);

			$this->piezas->actualizarResultadoCompuertas( $pieza->id, $resultado, $this->reloj->ahora() );

			if ( $resultado->retenida ) {
				$this->transicionador->transitar( $pieza->id, EstadoPieza::Retenida, implode( ' ', $resultado->motivos ) );

				return;
			}

			if ( ModoOperacion::Piloto === $resultado->modoEfectivo ) {
				return;
			}

			$this->transicionador->transitar( $pieza->id, EstadoPieza::Aprobada, 'compuertas superadas, modo ' . $resultado->modoEfectivo->value );
		} catch ( Throwable $e ) {
			$this->marcarFallida( $pieza->id, $e, $errores );
		}
	}

	/**
	 * Aprobada → Programada (Libro Cap. 9.2-9.3): cuota elástica, ventanas
	 * con peso, separación mínima + jitter, topes por vertical/periodista.
	 * Sin espacio hoy, la pieza espera al próximo tick (Cap. 9.3: "mejor
	 * esperar que publicar de más").
	 *
	 * @param list<string> $errores
	 */
	private function procesarProgramacion( Pieza $pieza, array &$errores ): void {
		try {
			if ( null === $pieza->fichaDecisionEditorial ) {
				return;
			}

			$ahora     = $this->reloj->ahora();
			$inicioDia = $ahora->setTime( 0, 0 );
			$finDia    = $inicioDia->modify( '+1 day' );

			$config         = $this->lectorCadencia->leer();
			$yaProgramadas  = $this->colaPublicacion->obtenerEntre( $inicioDia, $finDia );
			$vertical       = $pieza->fichaDecisionEditorial->clasificacion->tema;
			$horaProgramada = $this->programadorCadencia->siguienteRanura( $config, $yaProgramadas, $vertical, $pieza->periodistaId, $ahora );

			if ( null === $horaProgramada ) {
				return;
			}

			$this->colaPublicacion->programar( $pieza->id, $vertical, $pieza->periodistaId, $horaProgramada, $ahora );
			$this->transicionador->transitar( $pieza->id, EstadoPieza::Programada, 'programada para ' . $horaProgramada->format( 'Y-m-d H:i' ) );
		} catch ( Throwable $e ) {
			$this->marcarFallida( $pieza->id, $e, $errores );
		}
	}

	/**
	 * Programada → Publicada (Libro Cap. 9.3, paso 5): convierte el
	 * borrador ya creado en post publicado. Copiloto respeta la ventana de
	 * veto (Cap. 2.4: "veto con ventana") — Autónomo publica sin esperar.
	 *
	 * @param list<string> $errores
	 */
	private function procesarPublicacionesVencidas( array &$errores ): void {
		$ahora            = $this->reloj->ahora();
		$ventanaVetoHoras = $this->ventanaVetoHorasConfigurada();

		foreach ( $this->colaPublicacion->obtenerVencidas( $ahora ) as $ranura ) {
			try {
				$pieza = $this->piezas->obtenerPorId( $ranura->piezaId );

				if ( null === $pieza || EstadoPieza::Programada !== $pieza->estado || null === $pieza->postId ) {
					continue;
				}

				$modoEfectivo = $pieza->resultadoCompuertas->modoEfectivo ?? ModoOperacion::Copiloto;

				if ( ModoOperacion::Copiloto === $modoEfectivo && $ahora < $ranura->horaProgramada->modify( "+{$ventanaVetoHoras} hours" ) ) {
					continue;
				}

				$metadatos = $pieza->datosSeo->metadatos ?? new MetadatosSeo( '', '' );
				$plugin    = $pieza->datosSeo->pluginDetectado ?? TipoPluginSeo::Ninguno;
				$taxonomia = $pieza->resultadoTaxonomia ?? new ResultadoTaxonomia( null, array() );

				$this->publicador->publicar( $pieza->postId, $metadatos, $plugin, $taxonomia );
				$this->colaPublicacion->marcarPublicada( $ranura->id );
				$this->transicionador->transitar( $pieza->id, EstadoPieza::Publicada, 'publicada por el Orquestador' );
			} catch ( Throwable $e ) {
				$errores[] = "ranura {$ranura->id} (pieza {$ranura->piezaId}): " . $e->getMessage();
			}
		}
	}

	/**
	 * Escasez honesta (Libro Cap. 9.3, paso 6; CLAUDE.md § Contrato del
	 * Orquestador): "PROHIBIDO rebajar umbrales para rellenar". Esta función
	 * solo OBSERVA y registra — ninguna rama de código de esta clase toca
	 * los umbrales de las Compuertas.
	 *
	 * @param list<string> $errores
	 */
	private function verificarEscasezHonesta( array &$errores ): void {
		$ahora     = $this->reloj->ahora();
		$inicioDia = $ahora->setTime( 0, 0 );
		$finDia    = $inicioDia->modify( '+1 day' );
		$config    = $this->lectorCadencia->leer();

		$comprometidasHoy = count( $this->colaPublicacion->obtenerEntre( $inicioDia, $finDia ) );

		if ( $comprometidasHoy < $config->cuotaMinima ) {
			$errores[] = sprintf(
				'Escasez honesta: %d/%d piezas comprometidas hoy (mínimo %d, objetivo %d) — no se rebajan umbrales para rellenar.',
				$comprometidasHoy,
				$config->cuotaMaxima,
				$config->cuotaMinima,
				$config->cuotaObjetivo
			);
		}
	}

	private function modoGlobalConfigurado(): ModoOperacion {
		$valor = get_option( self::OPCION_MODO_OPERACION, self::MODO_OPERACION_DEFECTO );

		return ModoOperacion::tryFrom( is_string( $valor ) ? $valor : '' ) ?? ModoOperacion::Copiloto;
	}

	private function ventanaVetoHorasConfigurada(): int {
		$valor = get_option( self::OPCION_VENTANA_VETO_HORAS, self::VENTANA_VETO_HORAS_DEFECTO );

		return is_numeric( $valor ) ? (int) $valor : self::VENTANA_VETO_HORAS_DEFECTO;
	}

	/**
	 * Material para la Compuerta de Originalidad (Libro Cap. 8.3):
	 * auto-plagio/canibalización contra el propio sitio.
	 *
	 * @return list<string>
	 */
	private function textosPropiosRecientes(): array {
		$idsRecientes = get_posts(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => self::PIEZAS_PROPIAS_RECIENTES,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		return array_map(
			static function ( int $postId ): string {
				$contenido = get_post_field( 'post_content', $postId );

				return is_string( $contenido ) ? $contenido : '';
			},
			$idsRecientes
		);
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
