<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestPortada;
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
 * La Portada (Libro Cap. 10.2): "el día de un vistazo" contra WordPress
 * real. Protegida con `pluma_configurar_motor`, la misma capacidad que abre
 * la página del panel (Cap. 10.1) — nunca `manage_options`.
 *
 * @covers \Pluma\Admin\RestPortada
 */
final class RestPortadaTest extends WP_UnitTestCase {

	private function registrarRuta(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestPortada::class )->registrar();
		do_action( 'rest_api_init' );
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRuta();

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/panel/portada' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_administrador_obtiene_la_foto_del_dia(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId     = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia portada', PuntuacionOportunidad::calcular( 80, 80 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaRetenidaId = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		$repoPiezas->actualizarEstado( $piezaRetenidaId, EstadoPieza::Detectada, EstadoPieza::Retenida, $reloj->ahora() );

		$this->registrarRuta();

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/panel/portada' );
		$respuesta = rest_get_server()->dispatch( $peticion );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertSame( 'copiloto', $datos['modoOperacion'] );
		self::assertArrayHasKey( 'objetivo', $datos['cuota'] );
		self::assertArrayHasKey( 'gastoHoyUsd', $datos['salud'] );
		self::assertArrayHasKey( 'limiteDiarioUsd', $datos['salud'] );
		self::assertSame( 1, $datos['piezasPorEstado'][ EstadoPieza::Retenida->value ] );

		$idsRetenidas = array_map( static fn ( array $p ): int => $p['id'], $datos['alertas']['retenidas'] );
		self::assertContains( $piezaRetenidaId, $idsRetenidas );

		$terminosCalientes = array_map( static fn ( array $t ): string => $t['termino'], $datos['tendenciasCalientes'] );
		self::assertContains( 'tendencia portada', $terminosCalientes );
	}
}
