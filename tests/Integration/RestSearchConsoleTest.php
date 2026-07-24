<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestSearchConsole;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Bucle de Search Console (Libro Cap. 6.4) contra WordPress real: la misma
 * capacidad `pluma_configurar_motor` de la Sala de Máquinas, credenciales
 * cifradas nunca expuestas, y verificación de `state` anti-CSRF.
 *
 * Sin credenciales/refresh_token reales configurados en este entorno, los
 * caminos que exigirían red real (`sitios()`, `sincronizar()`, el
 * intercambio de código en `callback()`) siguen el mismo camino
 * determinista y sin red que ya usa `RestPeriodistasTest` para la vista
 * previa sin llave de OpenRouter: fallan de forma honesta ANTES de
 * cualquier llamada HTTP — el camino de éxito con red real está cubierto
 * por `ProveedorSearchConsoleTest` (Unit, con dobles de `wp_remote_*`).
 *
 * @covers \Pluma\Admin\RestSearchConsole
 */
final class RestSearchConsoleTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activador::activar( new RelojSistema(), '0.10.0' );
	}

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestSearchConsole::class )->registrar();
		do_action( 'rest_api_init' );
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/search-console/estado' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_estado_sin_conectar(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( 'pluma_search_console_refresh_token_cifrado' );
		delete_option( 'pluma_search_console_sitio' );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/search-console/estado' ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertFalse( $datos['conectada'] );
		self::assertNull( $datos['sitioSeleccionado'] );
		self::assertSame( array(), $datos['metricasRecientes'] );
	}

	public function test_guardar_credenciales_las_cifra_y_nunca_las_expone_en_texto_plano(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/search-console/credenciales' );
		$peticion->set_param( 'clientId', 'id-secreto-de-prueba' );
		$peticion->set_param( 'clientSecret', 'secreto-muy-secreto' );
		$respuesta = rest_get_server()->dispatch( $peticion );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertArrayHasKey( 'redirectUri', $datos );
		self::assertStringEndsWith( '/pluma/v1/search-console/callback', $datos['redirectUri'] );
		self::assertStringContainsString( 'accounts.google.com', $datos['urlAutorizacion'] );

		$clientIdCifrado     = get_option( 'pluma_search_console_client_id_cifrado' );
		$clientSecretCifrado = get_option( 'pluma_search_console_client_secret_cifrado' );

		self::assertIsString( $clientIdCifrado );
		self::assertStringStartsWith( 'pluma_v1:', $clientIdCifrado );
		self::assertStringNotContainsString( 'id-secreto-de-prueba', $clientIdCifrado );
		self::assertStringNotContainsString( 'secreto-muy-secreto', (string) $clientSecretCifrado );
		self::assertStringNotContainsString( 'secreto-muy-secreto', wp_json_encode( $datos ) );
	}

	public function test_guardar_credenciales_vacias_devuelve_400(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/search-console/credenciales' );
		$peticion->set_param( 'clientId', '' );
		$peticion->set_param( 'clientSecret', 'algo' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 400, $respuesta->get_status() );
	}

	public function test_borrar_credenciales_las_elimina_todas(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		update_option( 'pluma_search_console_refresh_token_cifrado', 'sobre-de-prueba' );
		update_option( 'pluma_search_console_sitio', 'https://sitio.test/' );

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'DELETE', '/pluma/v1/search-console/credenciales' ) );

		self::assertSame( 200, $respuesta->get_status() );
		self::assertFalse( get_option( 'pluma_search_console_refresh_token_cifrado' ) );
		self::assertFalse( get_option( 'pluma_search_console_sitio' ) );
	}

	public function test_callback_con_state_invalido_redirige_con_estado_invalido(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_transient( 'pluma_search_console_state' );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'GET', '/pluma/v1/search-console/callback' );
		$peticion->set_param( 'state', 'lo-que-sea' );
		$peticion->set_param( 'code', 'codigo-de-prueba' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 302, $respuesta->get_status() );
		self::assertStringContainsString( 'search_console=estado_invalido', $respuesta->get_headers()['Location'] );
	}

	public function test_sitios_sin_conectar_devuelve_409(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( 'pluma_search_console_refresh_token_cifrado' );
		delete_transient( 'pluma_search_console_access_token' );
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/search-console/sitios' ) );

		self::assertSame( 409, $respuesta->get_status() );
	}

	public function test_sincronizar_sin_conectar_devuelve_409(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( 'pluma_search_console_refresh_token_cifrado' );
		delete_transient( 'pluma_search_console_access_token' );
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/search-console/sincronizar' ) );

		self::assertSame( 409, $respuesta->get_status() );
	}

	public function test_guardar_sitio_vacio_devuelve_400(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/search-console/sitio' );
		$peticion->set_param( 'siteUrl', '' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 400, $respuesta->get_status() );
	}

	public function test_guardar_sitio_valido_lo_persiste(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/search-console/sitio' );
		$peticion->set_param( 'siteUrl', 'https://sitio-de-prueba.test/' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );
		self::assertSame( 'https://sitio-de-prueba.test/', get_option( 'pluma_search_console_sitio' ) );
	}
}
