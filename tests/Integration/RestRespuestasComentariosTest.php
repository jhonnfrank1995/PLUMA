<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestRespuestasComentarios;
use Pluma\Datos\RepositorioPeriodistas;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioRespuestasComentarios;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\TendenciaDetectada;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Sala de Comentarios contra WordPress real (wp-env): capacidad propia
 * `pluma_aprobar_piezas` y publicación real del comentario al aprobar
 * (Libro Cap. 5.7, "el editor aprueba con un clic").
 *
 * @covers \Pluma\Admin\RestRespuestasComentarios
 * @covers \Pluma\Pipeline\GestorRespuestasComentarios
 */
final class RestRespuestasComentariosTest extends WP_UnitTestCase {

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestRespuestasComentarios::class )->registrar();
		do_action( 'rest_api_init' );
	}

	/**
	 * @return array{0: int, 1: int, 2: int} [piezaId, periodistaId, comentarioId]
	 */
	private function crearRespuestaPendiente(): array {
		global $wpdb;
		$repoTendencias  = new RepositorioTendencias( $wpdb );
		$repoPiezas      = new RepositorioPiezas( $wpdb );
		$repoPeriodistas = new RepositorioPeriodistas( $wpdb );
		$repoRespuestas  = new RepositorioRespuestasComentarios( $wpdb );
		$reloj           = new RelojSistema();

		$postId = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia comentarios ' . uniqid(), PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		$repoPiezas->actualizarPostId( $piezaId, $postId, $reloj->ahora() );

		$periodistaId = $repoPeriodistas->crear(
			'Valentina Ruiz',
			null,
			'Bio.',
			RolPeriodista::Columnista,
			array(),
			EstadoPeriodista::Activo,
			new Diales( 80, 55, 40, 55, 75, 60, 60, 65 ),
			new ReglasConducta( 'linea', array(), array(), array(), TratamientoLector::Tu, '¿Y tú?' ),
			MatrizTonos::desdeFilas( array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) ) ),
			$reloj->ahora()
		);

		$comentarioId = wp_insert_comment(
			array(
				'comment_post_ID'  => $postId,
				'comment_author'   => 'Un lector',
				'comment_content'  => 'No estoy de acuerdo con el análisis, faltó contexto.',
				'comment_approved' => 1,
			)
		);
		self::assertIsInt( $comentarioId );

		$respuestaId = $repoRespuestas->registrar(
			$piezaId,
			$comentarioId,
			$periodistaId,
			'Gracias por el comentario, aquí va más contexto.',
			EstadoRespuestaComentario::PendienteAprobacion,
			$reloj->ahora()
		);

		return array( $respuestaId, $piezaId, $comentarioId );
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/comentarios/pendientes' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_administrador_ve_los_borradores_pendientes(): void {
		Activador::activar( new RelojSistema(), '0.12.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$respuestaId] = $this->crearRespuestaPendiente();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/comentarios/pendientes' ) );
		self::assertSame( 200, $respuesta->get_status() );

		$ids = array_column( $respuesta->get_data(), 'id' );
		self::assertContains( $respuestaId, $ids );
	}

	public function test_aprobar_publica_el_comentario_real_y_lo_saca_de_pendientes(): void {
		Activador::activar( new RelojSistema(), '0.12.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$respuestaId, , $comentarioOriginalId] = $this->crearRespuestaPendiente();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/comentarios/{$respuestaId}/aprobar" ) );
		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$respuestaPersistida = ( new RepositorioRespuestasComentarios( $wpdb ) )->obtenerPorId( $respuestaId );
		self::assertSame( EstadoRespuestaComentario::Aprobado, $respuestaPersistida->estado );
		self::assertNotNull( $respuestaPersistida->comentarioRespuestaId );

		$comentarioReal = get_comment( $respuestaPersistida->comentarioRespuestaId );
		self::assertNotNull( $comentarioReal );
		self::assertSame( 'Valentina Ruiz', $comentarioReal->comment_author );
		self::assertSame( 'Gracias por el comentario, aquí va más contexto.', $comentarioReal->comment_content );
		self::assertSame( (string) $comentarioOriginalId, $comentarioReal->comment_parent );

		$pendientes = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/comentarios/pendientes' ) )->get_data();
		self::assertNotContains( $respuestaId, array_column( $pendientes, 'id' ) );
	}

	public function test_descartar_saca_el_borrador_de_pendientes_sin_publicar_nada(): void {
		Activador::activar( new RelojSistema(), '0.12.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$respuestaId] = $this->crearRespuestaPendiente();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/comentarios/{$respuestaId}/descartar" ) );
		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$respuestaPersistida = ( new RepositorioRespuestasComentarios( $wpdb ) )->obtenerPorId( $respuestaId );
		self::assertSame( EstadoRespuestaComentario::Descartado, $respuestaPersistida->estado );
		self::assertNull( $respuestaPersistida->comentarioRespuestaId );
	}

	public function test_aprobar_dos_veces_la_misma_respuesta_devuelve_409(): void {
		Activador::activar( new RelojSistema(), '0.12.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$respuestaId] = $this->crearRespuestaPendiente();

		$this->registrarRutas();

		rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/comentarios/{$respuestaId}/aprobar" ) );
		$segundaVez = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/comentarios/{$respuestaId}/aprobar" ) );

		self::assertSame( 409, $segundaVez->get_status() );
	}

	public function test_una_respuesta_inexistente_devuelve_404(): void {
		Activador::activar( new RelojSistema(), '0.12.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/comentarios/999999/aprobar' ) );

		self::assertSame( 404, $respuesta->get_status() );
	}
}
