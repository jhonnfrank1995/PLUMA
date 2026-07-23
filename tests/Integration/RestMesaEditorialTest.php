<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestMesaEditorial;
use Pluma\Datos\RepositorioBorradores;
use Pluma\Datos\RepositorioPeriodistas;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\Especialidad;
use Pluma\Redaccion\EstadoPeriodista;
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
 * Mesa Editorial (Libro Cap. 10.2) contra WordPress real (wp-env): capacidad
 * propia `pluma_aprobar_piezas`, reasignación de periodista, edición manual
 * de un ciclo de borrador y "forzar aprobación" limitado a RETENIDA.
 *
 * @covers \Pluma\Admin\RestMesaEditorial
 */
final class RestMesaEditorialTest extends WP_UnitTestCase {

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestMesaEditorial::class )->registrar();
		do_action( 'rest_api_init' );
	}

	private function crearPieza( EstadoPieza $estado ): int {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia mesa ' . uniqid(), PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		$repoPiezas->actualizarEstado( $piezaId, EstadoPieza::Detectada, $estado, $reloj->ahora() );

		return $piezaId;
	}

	private function crearPeriodistaActivo( string $nombre ): int {
		global $wpdb;
		$repo  = new RepositorioPeriodistas( $wpdb );
		$reloj = new RelojSistema();

		return $repo->crear(
			$nombre,
			null,
			'Biografía de prueba.',
			RolPeriodista::Columnista,
			array( new Especialidad( 'economia', 5 ) ),
			EstadoPeriodista::Activo,
			new Diales(
				agudezaCritica: 60,
				humor: 40,
				satira: 20,
				formalidad: 60,
				vehemencia: 50,
				empatia: 50,
				densidadDatos: 60,
				longitudPreferida: 50
			),
			new ReglasConducta( 'Línea de prueba.', array(), array(), array(), TratamientoLector::Tu, 'Pregunta de cierre.' ),
			MatrizTonos::desdeFilas(
				array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
			),
			$reloj->ahora()
		);
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/piezas/kanban' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_el_kanban_agrupa_las_piezas_por_estado(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaId = $this->crearPieza( EstadoPieza::Retenida );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/piezas/kanban' ) );

		self::assertSame( 200, $respuesta->get_status() );
		$columna = $respuesta->get_data()['retenida'];
		self::assertContains( $piezaId, array_map( static fn ( array $p ): int => $p['id'], $columna ) );
	}

	public function test_el_detalle_incluye_expediente_ficha_compuertas_y_borradores(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaId = $this->crearPieza( EstadoPieza::Optimizada );

		global $wpdb;
		( new RepositorioBorradores( $wpdb ) )->crear( $piezaId, 1, 'Contenido del primer ciclo.', array(), true, ( new RelojSistema() )->ahora() );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', "/pluma/v1/piezas/{$piezaId}" ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertSame( 'optimizada', $datos['estado'] );
		self::assertNull( $datos['expediente'] );
		self::assertCount( 1, $datos['borradores'] );
		self::assertSame( 'Contenido del primer ciclo.', $datos['borradores'][0]['contenido'] );
		self::assertFalse( $datos['borradores'][0]['editadoManualmente'] );
	}

	public function test_reasignar_cambia_el_periodista_de_la_pieza(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaId      = $this->crearPieza( EstadoPieza::Optimizada );
		$periodistaId = $this->crearPeriodistaActivo( 'Periodista de Prueba' );

		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', "/pluma/v1/piezas/{$piezaId}/reasignar" );
		$peticion->set_param( 'periodistaId', $periodistaId );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$pieza = ( new RepositorioPiezas( $wpdb ) )->obtenerPorId( $piezaId );
		self::assertSame( $periodistaId, $pieza->periodistaId );
	}

	public function test_reasignar_una_pieza_publicada_devuelve_409(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaId      = $this->crearPieza( EstadoPieza::Publicada );
		$periodistaId = $this->crearPeriodistaActivo( 'Otro Periodista' );

		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', "/pluma/v1/piezas/{$piezaId}/reasignar" );
		$peticion->set_param( 'periodistaId', $periodistaId );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 409, $respuesta->get_status() );
	}

	public function test_editar_crea_un_nuevo_ciclo_marcado_como_manual(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaId = $this->crearPieza( EstadoPieza::Optimizada );

		global $wpdb;
		( new RepositorioBorradores( $wpdb ) )->crear( $piezaId, 1, 'Primer ciclo.', array(), true, ( new RelojSistema() )->ahora() );

		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', "/pluma/v1/piezas/{$piezaId}/editar" );
		$peticion->set_param( 'contenido', 'Texto corregido a mano por el editor.' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );

		$borradores = ( new RepositorioBorradores( $wpdb ) )->obtenerPorPieza( $piezaId );
		self::assertCount( 2, $borradores );
		self::assertSame( 2, $borradores[1]->numeroCiclo );
		self::assertTrue( $borradores[1]->editadoManualmente );
		self::assertStringContainsString( 'Texto corregido a mano', $borradores[1]->contenido );
	}

	public function test_aprobar_solo_funciona_sobre_una_pieza_retenida(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$piezaOptimizadaId = $this->crearPieza( EstadoPieza::Optimizada );

		$this->registrarRutas();

		$respuestaRechazada = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/piezas/{$piezaOptimizadaId}/aprobar" ) );
		self::assertSame( 409, $respuestaRechazada->get_status() );

		$piezaRetenidaId = $this->crearPieza( EstadoPieza::Retenida );
		$respuestaOk     = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/piezas/{$piezaRetenidaId}/aprobar" ) );
		self::assertSame( 200, $respuestaOk->get_status() );

		global $wpdb;
		$pieza = ( new RepositorioPiezas( $wpdb ) )->obtenerPorId( $piezaRetenidaId );
		self::assertSame( EstadoPieza::Aprobada, $pieza->estado );
	}

	public function test_descartar_una_pieza_inexistente_devuelve_404(): void {
		Activador::activar( new RelojSistema(), '0.9.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/piezas/999999/descartar' ) );

		self::assertSame( 404, $respuesta->get_status() );
	}
}
