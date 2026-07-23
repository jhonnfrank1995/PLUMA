<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestOnboarding;
use Pluma\Datos\RepositorioVocabulario;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\Orquestador;
use Pluma\Taxonomia\TipoVocabulario;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Asistente de instalación de 5 actos (Libro Cap. 10.3) contra WordPress
 * real: la misma capacidad `pluma_configurar_motor` del resto de pantallas
 * técnicas, importación real de categorías de WordPress, escritura real del
 * modo de operación (hasta ahora solo se leía) y el primer ciclo disparando
 * `Orquestador::ejecutarTick()` de verdad.
 *
 * @covers \Pluma\Admin\RestOnboarding
 */
final class RestOnboardingTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activador::activar( new RelojSistema(), '0.9.0' );
	}

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestOnboarding::class )->registrar();
		do_action( 'rest_api_init' );
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/onboarding/estado-tecnico' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_estado_tecnico_incluye_los_datos_reales_del_cron(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/onboarding/estado-tecnico' ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertSame( PHP_VERSION, $datos['versionPhp'] );
		self::assertStringEndsWith( '/pluma/v1/motor/tick', $datos['cron']['url'] );
		self::assertSame( 'X-Pluma-Token', $datos['cron']['cabecera'] );
		self::assertSame( get_option( Activador::OPCION_MOTOR_TOKEN ), $datos['cron']['token'] );
	}

	public function test_importar_categorias_las_trae_de_wordpress_real_y_es_idempotente(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$sufijo    = uniqid();
		$categoria = self::factory()->category->create_and_get( array( 'name' => 'Economía ' . $sufijo ) );

		$primera = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/onboarding/importar-categorias' ) );
		$datos   = $primera->get_data();

		self::assertSame( 200, $primera->get_status() );
		self::assertContains( $categoria->name, $datos['importadas'] );

		global $wpdb;
		$repo    = new RepositorioVocabulario( $wpdb );
		$entrada = $repo->obtenerPorTipoYSlug( TipoVocabulario::Categoria, $categoria->slug );
		self::assertNotNull( $entrada );

		$segunda      = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/onboarding/importar-categorias' ) );
		$datosSegunda = $segunda->get_data();

		self::assertContains( $categoria->name, $datosSegunda['yaExistian'] );
		self::assertNotContains( $categoria->name, $datosSegunda['importadas'] );
	}

	public function test_guardar_modo_persiste_un_valor_valido(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/onboarding/modo' );
		$peticion->set_param( 'modo', 'autonomo' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );
		self::assertSame( 'autonomo', get_option( Orquestador::OPCION_MODO_OPERACION ) );
	}

	public function test_guardar_modo_invalido_devuelve_400(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/onboarding/modo' );
		$peticion->set_param( 'modo', 'inventado' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 400, $respuesta->get_status() );
	}

	public function test_primer_ciclo_ejecuta_el_orquestador_de_verdad(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/onboarding/primer-ciclo' ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );
		self::assertArrayHasKey( 'ejecutado', $datos );
		self::assertArrayHasKey( 'lotesProcesados', $datos );
	}

	public function test_completar_marca_la_opcion_de_onboarding(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->registrarRutas();

		delete_option( RestOnboarding::OPCION_COMPLETADO );

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/onboarding/completar' ) );

		self::assertSame( 200, $respuesta->get_status() );
		self::assertTrue( (bool) get_option( RestOnboarding::OPCION_COMPLETADO ) );
	}
}
