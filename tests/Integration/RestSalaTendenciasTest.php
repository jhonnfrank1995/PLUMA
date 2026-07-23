<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestSalaTendencias;
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
 * Sala de Tendencias contra WordPress real (wp-env): capacidad propia
 * `pluma_aprobar_piezas` (intervenir la agenda es decisión editorial) y las
 * tres acciones directas del Libro Cap. 10.2 de punta a punta.
 *
 * @covers \Pluma\Admin\RestSalaTendencias
 * @covers \Pluma\Pipeline\GestorSalaTendencias
 */
final class RestSalaTendenciasTest extends WP_UnitTestCase {

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestSalaTendencias::class )->registrar();
		do_action( 'rest_api_init' );
	}

	/**
	 * @return array{0: int, 1: int} [tendenciaId, piezaId]
	 */
	private function crearTendenciaConPieza(): array {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada(
				'tendencia sala ' . uniqid(),
				PuntuacionOportunidad::calcular( 80, 60 ),
				$reloj->ahora(),
				array(
					array(
						'titulo' => 'Cobertura previa',
						'url'    => 'https://example.com/a',
						'fuente' => 'Example',
					),
				),
				'google_trends'
			),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );

		return array( $tendenciaId, $piezaId );
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/tendencias' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_administrador_ve_las_tarjetas_con_desglose_y_cobertura(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$tendenciaId] = $this->crearTendenciaConPieza();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/tendencias' ) );

		self::assertSame( 200, $respuesta->get_status() );

		$tarjeta = $this->tarjetaDe( $respuesta->get_data(), $tendenciaId );
		self::assertNotNull( $tarjeta );
		self::assertSame( 80.0, $tarjeta['velocidad'] );
		self::assertSame( 60.0, $tarjeta['afinidad'] );
		self::assertSame( 'en_pipeline', $tarjeta['estado'] );
		self::assertSame( 'Cobertura previa', $tarjeta['articulosRelacionados'][0]['titulo'] );
	}

	public function test_vigilar_descarta_la_pieza_y_la_tarjeta_queda_vigilada(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$tendenciaId, $piezaId] = $this->crearTendenciaConPieza();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/tendencias/{$tendenciaId}/vigilar" ) );
		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$pieza = ( new RepositorioPiezas( $wpdb ) )->obtenerPorId( $piezaId );
		self::assertSame( EstadoPieza::Descartada, $pieza->estado );

		$tarjetas = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/tendencias' ) )->get_data();
		self::assertSame( 'vigilada', $this->tarjetaDe( $tarjetas, $tendenciaId )['estado'] );
	}

	public function test_cubrir_una_tendencia_vigilada_crea_una_pieza_nueva_prioritaria(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$tendenciaId, $piezaOriginalId] = $this->crearTendenciaConPieza();
		// Otra pieza DETECTADA más antigua y sin prioridad: sin "saltar la
		// cola", encabezaría el lote por antigüedad.
		$this->crearTendenciaConPieza();

		$this->registrarRutas();

		rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/tendencias/{$tendenciaId}/vigilar" ) );
		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/tendencias/{$tendenciaId}/cubrir" ) );
		self::assertSame( 200, $respuesta->get_status() );

		global $wpdb;
		$repoPiezas = new RepositorioPiezas( $wpdb );

		$piezaNueva = $repoPiezas->obtenerUltimaPorTendencia( $tendenciaId );
		self::assertNotSame( $piezaOriginalId, $piezaNueva->id );
		self::assertSame( EstadoPieza::Detectada, $piezaNueva->estado );

		// La pieza prioritaria encabeza el lote aunque otra sea más antigua.
		$lote = $repoPiezas->obtenerPorEstado( EstadoPieza::Detectada, 10 );
		self::assertSame( $piezaNueva->id, $lote[0]->id );

		$tarjetas = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/tendencias' ) )->get_data();
		self::assertSame( 'en_pipeline', $this->tarjetaDe( $tarjetas, $tendenciaId )['estado'] );
	}

	public function test_ignorar_saca_la_tarjeta_de_la_sala(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		[$tendenciaId] = $this->crearTendenciaConPieza();

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', "/pluma/v1/tendencias/{$tendenciaId}/ignorar" ) );
		self::assertSame( 200, $respuesta->get_status() );

		$tarjetas = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/tendencias' ) )->get_data();
		self::assertNull( $this->tarjetaDe( $tarjetas, $tendenciaId ) );
	}

	public function test_una_tendencia_inexistente_devuelve_404(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'POST', '/pluma/v1/tendencias/999999/cubrir' ) );

		self::assertSame( 404, $respuesta->get_status() );
	}

	/**
	 * @param list<array<string, mixed>> $tarjetas
	 * @return array<string, mixed>|null
	 */
	private function tarjetaDe( array $tarjetas, int $tendenciaId ): ?array {
		foreach ( $tarjetas as $tarjeta ) {
			if ( $tarjeta['id'] === $tendenciaId ) {
				return $tarjeta;
			}
		}

		return null;
	}
}
