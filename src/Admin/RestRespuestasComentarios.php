<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Kernel\Capacidades;
use Pluma\Pipeline\GestorRespuestasComentarios;
use Pluma\Pipeline\RespuestaComentarioEstadoInvalidoException;
use Pluma\Pipeline\RespuestaComentarioNoEncontradaException;
use Pluma\Publicacion\PublicacionComentarioException;
use Pluma\Redaccion\RespuestaComentario;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Sala de Comentarios (Libro Cap. 5.7, Etapa 5): bandeja de borradores de
 * respuesta pendientes de aprobación humana. Protegido con
 * `pluma_aprobar_piezas` — publicar una respuesta pública en nombre del
 * periodista es una decisión editorial, no configuración del motor.
 */
final class RestRespuestasComentarios {

	private const RUTA_PENDIENTES = '/comentarios/pendientes';
	private const RUTA_APROBAR    = '/comentarios/(?P<id>\d+)/aprobar';
	private const RUTA_DESCARTAR  = '/comentarios/(?P<id>\d+)/descartar';

	public function __construct( private readonly GestorRespuestasComentarios $gestor ) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_PENDIENTES,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'pendientes' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		foreach ( array(
			self::RUTA_APROBAR   => 'aprobar',
			self::RUTA_DESCARTAR => 'descartar',
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

	public function pendientes(): WP_REST_Response {
		$pendientes = array_map(
			static fn ( RespuestaComentario $r ): array => array(
				'id'           => $r->id,
				'piezaId'      => $r->piezaId,
				'comentarioId' => $r->comentarioId,
				'periodistaId' => $r->periodistaId,
				'borrador'     => $r->borrador,
				'creadaEn'     => $r->creadaEn->format( DATE_ATOM ),
			),
			$this->gestor->obtenerPendientes()
		);

		return new WP_REST_Response( $pendientes, 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function aprobar( WP_REST_Request $request ) {
		return $this->ejecutarAccion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestor->aprobar( $id ) );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function descartar( WP_REST_Request $request ) {
		return $this->ejecutarAccion( (int) $request->get_param( 'id' ), fn ( int $id ) => $this->gestor->descartar( $id ) );
	}

	/**
	 * @param callable(int): void $accion
	 * @return WP_REST_Response|WP_Error
	 */
	private function ejecutarAccion( int $respuestaId, callable $accion ) {
		try {
			$accion( $respuestaId );
		} catch ( RespuestaComentarioNoEncontradaException $e ) {
			return new WP_Error( 'pluma_respuesta_no_encontrada', $e->getMessage(), array( 'status' => 404 ) );
		} catch ( RespuestaComentarioEstadoInvalidoException $e ) {
			return new WP_Error( 'pluma_respuesta_estado_invalido', $e->getMessage(), array( 'status' => 409 ) );
		} catch ( PublicacionComentarioException $e ) {
			return new WP_Error( 'pluma_publicacion_comentario_fallida', $e->getMessage(), array( 'status' => 502 ) );
		}

		return new WP_REST_Response( array( 'id' => $respuestaId ), 200 );
	}
}
