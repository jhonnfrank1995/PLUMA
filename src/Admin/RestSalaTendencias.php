<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Kernel\Capacidades;
use Pluma\Pipeline\GestorSalaTendencias;
use Pluma\Pipeline\TendenciaNoEncontradaException;
use Pluma\Pipeline\TransicionInvalidaException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Sala de Tendencias (Libro Cap. 10.2): "el radar en vivo" con acciones
 * directas sobre la agenda. Protegido con `pluma_aprobar_piezas` — intervenir
 * la agenda es una decisión editorial, no configuración del motor
 * (CLAUDE.md § Estándares WordPress: nunca `manage_options`).
 */
final class RestSalaTendencias {

	private const RUTA_TARJETAS = '/tendencias';
	private const RUTA_CUBRIR   = '/tendencias/(?P<id>\d+)/cubrir';
	private const RUTA_IGNORAR  = '/tendencias/(?P<id>\d+)/ignorar';
	private const RUTA_VIGILAR  = '/tendencias/(?P<id>\d+)/vigilar';

	public function __construct( private readonly GestorSalaTendencias $gestor ) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_TARJETAS,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'tarjetas' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		foreach ( array(
			self::RUTA_CUBRIR  => 'cubrir',
			self::RUTA_IGNORAR => 'ignorar',
			self::RUTA_VIGILAR => 'vigilar',
		) as $ruta => $metodo ) {
			register_rest_route(
				'pluma/v1',
				$ruta,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, $metodo ),
					'permission_callback' => array( $this, 'autorizado' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => static fn ( $valor ): bool => is_numeric( $valor ),
						),
					),
				)
			);
		}
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::APROBAR_PIEZAS );
	}

	public function tarjetas(): WP_REST_Response {
		$tarjetas = array_map(
			static fn ( array $tarjeta ): array => array(
				...$tarjeta,
				'estado' => $tarjeta['estado']->value,
			),
			$this->gestor->obtenerTarjetas()
		);

		return new WP_REST_Response( $tarjetas, 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function cubrir( WP_REST_Request $request ) {
		return $this->ejecutarAccion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestor->cubrirAhora( $id ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function ignorar( WP_REST_Request $request ) {
		return $this->ejecutarAccion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestor->ignorar( $id ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function vigilar( WP_REST_Request $request ) {
		return $this->ejecutarAccion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestor->vigilar( $id ) );
	}

	/**
	 * @param callable(int): void $accion
	 * @return WP_REST_Response|WP_Error
	 */
	private function ejecutarAccion( int $tendenciaId, callable $accion ) {
		try {
			$accion( $tendenciaId );
		} catch ( TendenciaNoEncontradaException $e ) {
			return new WP_Error( 'pluma_tendencia_no_encontrada', $e->getMessage(), array( 'status' => 404 ) );
		} catch ( TransicionInvalidaException $e ) {
			return new WP_Error( 'pluma_transicion_invalida', $e->getMessage(), array( 'status' => 409 ) );
		}

		return new WP_REST_Response( array( 'tendenciaId' => $tendenciaId ), 200 );
	}
}
