<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestSalaRevision;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\TendenciaDetectada;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Sala de Revisión contra WordPress real (wp-env): capacidad propia
 * `pluma_aprobar_piezas`, jamás `manage_options` (Libro Cap. 10.2,
 * CLAUDE.md § Estándares WordPress).
 *
 * @covers \Pluma\Admin\RestSalaRevision
 * @covers \Pluma\Pipeline\GestorSalaRevision
 */
final class RestSalaRevisionTest extends WP_UnitTestCase {

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestSalaRevision::class )->registrar();
		do_action( 'rest_api_init' );
	}

	/**
	 * Crea una Pieza y la fuerza a `$estado` directamente vía el repositorio
	 * (sin pasar por `Transicionador`, deliberado: esto es fixture de test,
	 * no código de producción — el grafo de estados solo protege las
	 * transiciones reales del Orquestador/Sala de Revisión).
	 */
	private function crearPiezaEnEstado( EstadoPieza $estado ): int {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia revision ' . uniqid(), PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		$repoPiezas->actualizarEstado( $piezaId, EstadoPieza::Detectada, $estado, $reloj->ahora() );

		return $piezaId;
	}

	public function test_retenidas_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/revision/retenidas' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_administrador_ve_las_piezas_retenidas(): void {
		Activador::activar( new RelojSistema(), '0.7.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$piezaId = $this->crearPiezaEnEstado( EstadoPieza::Retenida );

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/revision/retenidas' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );
		$ids = array_map( static fn ( array $p ): int => $p['id'], $respuesta->get_data() );
		self::assertContains( $piezaId, $ids );
	}

	public function test_aprobar_mueve_la_pieza_a_aprobada(): void {
		Activador::activar( new RelojSistema(), '0.7.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$piezaId = $this->crearPiezaEnEstado( EstadoPieza::Retenida );

		$peticion  = new WP_REST_Request( 'POST', "/pluma/v1/revision/{$piezaId}/aprobar" );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$pieza = ( new RepositorioPiezas( $wpdb ) )->obtenerPorId( $piezaId );
		self::assertSame( EstadoPieza::Aprobada, $pieza->estado );
	}

	public function test_devolver_mueve_la_pieza_a_optimizada(): void {
		Activador::activar( new RelojSistema(), '0.7.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$piezaId = $this->crearPiezaEnEstado( EstadoPieza::Retenida );

		$peticion = new WP_REST_Request( 'POST', "/pluma/v1/revision/{$piezaId}/devolver" );
		$peticion->set_param( 'nota', 'falta doble fuente' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$pieza = ( new RepositorioPiezas( $wpdb ) )->obtenerPorId( $piezaId );
		self::assertSame( EstadoPieza::Optimizada, $pieza->estado );
	}

	public function test_descartar_una_pieza_inexistente_devuelve_404(): void {
		Activador::activar( new RelojSistema(), '0.7.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$peticion  = new WP_REST_Request( 'POST', '/pluma/v1/revision/999999/descartar' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 404, $respuesta->get_status() );
	}
}
