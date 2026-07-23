<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Kernel\Capacidades;
use Pluma\Redaccion\ExportadorBancoPeriodistas;
use Pluma\Redaccion\ImportacionBancoException;
use Pluma\Redaccion\ImportadorBancoPeriodistas;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Export/import del banco de periodistas (pl-periodistas §8: "API pública
 * del producto"). Protegido con la capacidad propia `pluma_gestionar_periodistas`
 * (CLAUDE.md § Estándares WordPress) — nunca `manage_options`, nunca
 * `permission_callback` abierto en una ruta de escritura.
 */
final class RestBancoPeriodistas {

	private const RUTA_EXPORTAR = '/periodistas/exportar';
	private const RUTA_IMPORTAR = '/periodistas/importar';

	public function __construct(
		private readonly ExportadorBancoPeriodistas $exportador,
		private readonly ImportadorBancoPeriodistas $importador,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_EXPORTAR,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'exportar' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_IMPORTAR,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'importar' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::GESTIONAR_PERIODISTAS );
	}

	public function exportar(): WP_REST_Response {
		return new WP_REST_Response( $this->exportador->exportar(), 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function importar( WP_REST_Request $request ) {
		$datos = $request->get_json_params();

		if ( ! is_array( $datos ) ) {
			return new WP_Error(
				'pluma_importacion_invalida',
				__( 'El cuerpo de la petición debe ser el JSON de una exportación del banco de periodistas.', 'pluma-engine' ),
				array( 'status' => 400 )
			);
		}

		try {
			$importados = $this->importador->importar( $datos );
		} catch ( ImportacionBancoException $e ) {
			return new WP_Error( 'pluma_importacion_invalida', $e->getMessage(), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( array( 'importados' => $importados ), 201 );
	}
}
