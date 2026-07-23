<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Datos\RepositorioBorradoresInterface;
use Pluma\Datos\RepositorioPeriodistasInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\RelojInterface;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\GestorSalaRevision;
use Pluma\Pipeline\Pieza;
use Pluma\Pipeline\PiezaNoEncontradaException;
use Pluma\Pipeline\TransicionInvalidaException;
use Pluma\Redaccion\AnotacionCorrector;
use Pluma\Redaccion\Borrador;
use Pluma\Redaccion\EstadoPeriodista;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Mesa Editorial (Libro Cap. 10.2): "el kanban de Piezas por estado" con el
 * expediente completo, la Ficha de Decisión Editorial, el historial de
 * borradores con las anotaciones del Corrector Interno y el desglose de
 * Compuertas al abrir una Pieza. Protegido con `pluma_aprobar_piezas`
 * (intervenir una Pieza es decisión editorial, no configuración del motor).
 *
 * "Forzar aprobación" (decisión del propietario, 2026-07-23): es literalmente
 * `GestorSalaRevision::aprobar()` — solo tiene efecto sobre RETENIDA; el
 * grafo del Transicionador rechaza cualquier otro origen con 409. No existe
 * ninguna ruta aquí que publique una Pieza saltándose Compuertas.
 */
final class RestMesaEditorial {

	private const RUTA_KANBAN    = '/piezas/kanban';
	private const RUTA_DETALLE   = '/piezas/(?P<id>\d+)';
	private const RUTA_REASIGNAR = '/piezas/(?P<id>\d+)/reasignar';
	private const RUTA_APROBAR   = '/piezas/(?P<id>\d+)/aprobar';
	private const RUTA_DESCARTAR = '/piezas/(?P<id>\d+)/descartar';
	private const RUTA_EDITAR    = '/piezas/(?P<id>\d+)/editar';

	private const LIMITE_POR_COLUMNA = 20;
	private const ORIGEN             = 'la Mesa Editorial';

	public function __construct(
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioTendenciasInterface $tendencias,
		private readonly RepositorioPeriodistasInterface $periodistas,
		private readonly RepositorioBorradoresInterface $borradores,
		private readonly GestorSalaRevision $gestorSalaRevision,
		private readonly RelojInterface $reloj,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_KANBAN,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'kanban' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_DETALLE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'detalle' ),
				'permission_callback' => array( $this, 'autorizado' ),
				'args'                => $this->argumentoId(),
			)
		);

		foreach ( array(
			self::RUTA_REASIGNAR => 'reasignar',
			self::RUTA_APROBAR   => 'aprobar',
			self::RUTA_DESCARTAR => 'descartar',
			self::RUTA_EDITAR    => 'editar',
		) as $ruta => $metodo ) {
			register_rest_route(
				'pluma/v1',
				$ruta,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, $metodo ),
					'permission_callback' => array( $this, 'autorizado' ),
					'args'                => $this->argumentoId(),
				)
			);
		}
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::APROBAR_PIEZAS );
	}

	public function kanban(): WP_REST_Response {
		$columnas = array();

		foreach ( EstadoPieza::cases() as $estado ) {
			$columnas[ $estado->value ] = array_map(
				fn ( Pieza $pieza ): array => $this->resumenTarjeta( $pieza ),
				$this->piezas->obtenerPorEstado( $estado, self::LIMITE_POR_COLUMNA )
			);
		}

		return new WP_REST_Response( $columnas, 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function detalle( WP_REST_Request $request ) {
		$pieza = $this->piezas->obtenerPorId( (int) $request->get_param( 'id' ) );

		if ( null === $pieza ) {
			return $this->errorNoEncontrada( (int) $request->get_param( 'id' ) );
		}

		return new WP_REST_Response(
			array(
				'id'                     => $pieza->id,
				'estado'                 => $pieza->estado->value,
				'tendenciaTermino'       => $this->tendencias->obtenerPorId( $pieza->tendenciaId )['termino'] ?? null,
				'periodista'             => $this->periodistaResumen( $pieza->periodistaId ),
				'expediente'             => $pieza->expediente?->aArray(),
				'fichaDecisionEditorial' => $pieza->fichaDecisionEditorial?->aArray(),
				'resultadoCompuertas'    => $pieza->resultadoCompuertas?->aArray(),
				'postId'                 => $pieza->postId,
				'creadaEn'               => $pieza->creadaEn->format( DATE_ATOM ),
				'actualizadaEn'          => $pieza->actualizadaEn->format( DATE_ATOM ),
				'borradores'             => array_map( array( $this, 'borradorComoArray' ), $this->borradores->obtenerPorPieza( $pieza->id ) ),
				'periodistasActivos'     => array_map(
					static fn ( $p ): array => array(
						'id'     => $p->id,
						'nombre' => $p->nombre,
					),
					$this->periodistas->obtenerActivos()
				),
			),
			200
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function reasignar( WP_REST_Request $request ) {
		$piezaId = (int) $request->get_param( 'id' );
		$pieza   = $this->piezas->obtenerPorId( $piezaId );

		if ( null === $pieza ) {
			return $this->errorNoEncontrada( $piezaId );
		}

		if ( $pieza->estado->esTerminal() ) {
			return new WP_Error( 'pluma_pieza_no_editable', __( 'Una Pieza publicada o descartada no se puede reasignar.', 'pluma-engine' ), array( 'status' => 409 ) );
		}

		$periodistaId = $request->get_param( 'periodistaId' );

		if ( ! is_numeric( $periodistaId ) ) {
			return new WP_Error( 'pluma_periodista_invalido', __( 'Falta el periodista a asignar.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		$periodista = $this->periodistas->obtenerPorId( (int) $periodistaId );

		if ( null === $periodista || EstadoPeriodista::Activo !== $periodista->estado ) {
			return new WP_Error( 'pluma_periodista_no_encontrado', __( 'El periodista no existe o está jubilado.', 'pluma-engine' ), array( 'status' => 404 ) );
		}

		$this->piezas->asignarPeriodista( $piezaId, $periodista->id, $periodista->conductaActual->id, $this->reloj->ahora() );

		return new WP_REST_Response( array( 'piezaId' => $piezaId ), 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function aprobar( WP_REST_Request $request ) {
		return $this->ejecutarTransicion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestorSalaRevision->aprobar( $id, self::ORIGEN ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function descartar( WP_REST_Request $request ) {
		return $this->ejecutarTransicion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestorSalaRevision->descartar( $id, self::ORIGEN ) );
	}

	/**
	 * "Editar" (Libro Cap. 10.2): un nuevo ciclo de borrador escrito a mano,
	 * marcado `editadoManualmente` — no reescribe los ciclos anteriores, se
	 * suma al historial como cualquier otro (Cap. 11: "el historial de
	 * revisión").
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function editar( WP_REST_Request $request ) {
		$piezaId = (int) $request->get_param( 'id' );
		$pieza   = $this->piezas->obtenerPorId( $piezaId );

		if ( null === $pieza ) {
			return $this->errorNoEncontrada( $piezaId );
		}

		if ( $pieza->estado->esTerminal() ) {
			return new WP_Error( 'pluma_pieza_no_editable', __( 'Una Pieza publicada o descartada no se puede editar.', 'pluma-engine' ), array( 'status' => 409 ) );
		}

		$contenido = $request->get_param( 'contenido' );

		if ( ! is_string( $contenido ) || '' === trim( $contenido ) ) {
			return new WP_Error( 'pluma_contenido_vacio', __( 'El contenido no puede estar vacío.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		$ultimoCiclo = $this->borradores->obtenerUltimo( $piezaId );
		$nuevoCiclo  = null !== $ultimoCiclo ? $ultimoCiclo->numeroCiclo + 1 : 1;

		$this->borradores->crear(
			$piezaId,
			$nuevoCiclo,
			wp_kses_post( $contenido ),
			array(),
			true,
			$this->reloj->ahora(),
			true
		);

		return new WP_REST_Response( array( 'piezaId' => $piezaId ), 200 );
	}

	/**
	 * @param callable(int): void $accion
	 * @return WP_REST_Response|WP_Error
	 */
	private function ejecutarTransicion( int $piezaId, callable $accion ) {
		try {
			$accion( $piezaId );
		} catch ( PiezaNoEncontradaException $e ) {
			return new WP_Error( 'pluma_pieza_no_encontrada', $e->getMessage(), array( 'status' => 404 ) );
		} catch ( TransicionInvalidaException $e ) {
			return new WP_Error( 'pluma_transicion_invalida', $e->getMessage(), array( 'status' => 409 ) );
		}

		return new WP_REST_Response( array( 'piezaId' => $piezaId ), 200 );
	}

	/**
	 * @return WP_Error
	 */
	private function errorNoEncontrada( int $piezaId ) {
		return new WP_Error( 'pluma_pieza_no_encontrada', ( new PiezaNoEncontradaException( $piezaId ) )->getMessage(), array( 'status' => 404 ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resumenTarjeta( Pieza $pieza ): array {
		$ficha = $pieza->fichaDecisionEditorial;

		return array(
			'id'               => $pieza->id,
			'tendenciaTermino' => $this->tendencias->obtenerPorId( $pieza->tendenciaId )['termino'] ?? null,
			'periodista'       => $this->periodistaResumen( $pieza->periodistaId ),
			'tesisCorta'       => $ficha?->tesisElegida()->tesis,
			'tonoDominante'    => $ficha?->tonoDominante->value,
			'actualizadaEn'    => $pieza->actualizadaEn->format( DATE_ATOM ),
		);
	}

	/**
	 * @return array{id: int, nombre: string}|null
	 */
	private function periodistaResumen( ?int $periodistaId ): ?array {
		if ( null === $periodistaId ) {
			return null;
		}

		$periodista = $this->periodistas->obtenerPorId( $periodistaId );

		return null !== $periodista ? array(
			'id'     => $periodista->id,
			'nombre' => $periodista->nombre,
		) : null;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function borradorComoArray( Borrador $borrador ): array {
		return array(
			'id'                   => $borrador->id,
			'numeroCiclo'          => $borrador->numeroCiclo,
			'contenido'            => $borrador->contenido,
			'anotaciones'          => array_map( static fn ( AnotacionCorrector $a ): array => $a->aArray(), $borrador->anotaciones ),
			'aprobadoPorCorrector' => $borrador->aprobadoPorCorrector,
			'editadoManualmente'   => $borrador->editadoManualmente,
			'creadoEn'             => $borrador->creadoEn->format( DATE_ATOM ),
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function argumentoId(): array {
		return array(
			'id' => array(
				'required'          => true,
				'validate_callback' => static fn ( $valor ): bool => is_numeric( $valor ),
			),
		);
	}
}
