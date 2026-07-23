<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Proveedores;

use Brain\Monkey\Functions;
use Pluma\Proveedores\ProveedorGoogleTrends;
use Pluma\Proveedores\ProveedorTendenciasException;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * Fixture real capturada de https://trends.google.com/trending/rss?geo=US
 * el 2026-07-22 (recortada a 2 tendencias), GOVERNANCE §4.4/pl-proveedor-ia §5:
 * la suite jamás llama a la API real.
 *
 * @covers \Pluma\Proveedores\ProveedorGoogleTrends
 */
final class ProveedorGoogleTrendsTest extends CasoDePruebaUnitario {

	private function fixture(): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- lectura de un fixture local del repo, no una URL remota.
		$contenido = file_get_contents( __DIR__ . '/../../Fixtures/google-trends-2026-07-22.xml' );
		self::assertIsString( $contenido );

		return $contenido;
	}

	private function mockearPeticionExitosa(): void {
		Functions\when( 'add_query_arg' )->justReturn( 'https://trends.google.com/trending/rss?geo=US' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		// Evita una resolución DNS real (GOVERNANCE §4.4/pl-proveedor-ia §5: la suite jamás toca la red).
		Functions\when( 'gethostbyname' )->justReturn( '142.250.1.100' );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'wp_remote_get' )->justReturn( array( 'response' => array( 'code' => 200 ) ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( $this->fixture() );
	}

	public function test_interpreta_el_feed_real_y_devuelve_las_tendencias_crudas(): void {
		$this->mockearPeticionExitosa();

		$tendencias = ( new ProveedorGoogleTrends( new RelojFijo() ) )->obtenerTendenciasCrudas();

		self::assertCount( 2, $tendencias );
		self::assertSame( 'houston weather', $tendencias[0]->termino );
		self::assertSame( '20000+', $tendencias[0]->traficoAproximado );
		self::assertCount( 2, $tendencias[0]->articulosRelacionados );
		self::assertSame( 'FOX Weather', $tendencias[0]->articulosRelacionados[0]['fuente'] );
		self::assertSame( 'spencer torkelson', $tendencias[1]->termino );
		self::assertSame( '500+', $tendencias[1]->traficoAproximado );
	}

	public function test_lanza_excepcion_si_wp_remote_get_devuelve_wp_error(): void {
		Functions\when( 'add_query_arg' )->justReturn( 'https://trends.google.com/trending/rss?geo=US' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		// Evita una resolución DNS real (GOVERNANCE §4.4/pl-proveedor-ia §5: la suite jamás toca la red).
		Functions\when( 'gethostbyname' )->justReturn( '142.250.1.100' );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'wp_remote_get' )->justReturn( new \WP_Error( 'http_request_failed', 'Timeout' ) );
		Functions\when( 'is_wp_error' )->alias( static fn ( $valor ): bool => $valor instanceof \WP_Error );

		$this->expectException( ProveedorTendenciasException::class );

		( new ProveedorGoogleTrends( new RelojFijo() ) )->obtenerTendenciasCrudas();
	}

	public function test_lanza_excepcion_si_el_codigo_http_no_es_200(): void {
		Functions\when( 'add_query_arg' )->justReturn( 'https://trends.google.com/trending/rss?geo=US' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		// Evita una resolución DNS real (GOVERNANCE §4.4/pl-proveedor-ia §5: la suite jamás toca la red).
		Functions\when( 'gethostbyname' )->justReturn( '142.250.1.100' );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'wp_remote_get' )->justReturn( array( 'response' => array( 'code' => 503 ) ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 503 );

		$this->expectException( ProveedorTendenciasException::class );

		( new ProveedorGoogleTrends( new RelojFijo() ) )->obtenerTendenciasCrudas();
	}

	public function test_circuito_abierto_impide_una_nueva_llamada_de_red(): void {
		Functions\when( 'get_option' )->alias(
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInTernaryCondition.FoundInTernaryCondition -- falso positivo: es una arrow function con `=>`, no una asignación.
			static fn ( string $opcion, $defecto = false ) => 'pluma_proveedor_trends_abierto_hasta' === $opcion
				? ( new RelojFijo() )->ahora()->getTimestamp() + 100
				: $defecto
		);

		Functions\expect( 'wp_remote_get' )->never();

		$this->expectException( ProveedorTendenciasException::class );
		$this->expectExceptionMessageMatches( '/[Cc]ircuito abierto/' );

		( new ProveedorGoogleTrends( new RelojFijo() ) )->obtenerTendenciasCrudas();
	}

	public function test_circuito_abierto_expone_el_mismo_estado_para_la_sala_de_maquinas(): void {
		Functions\when( 'get_option' )->justReturn( ( new RelojFijo() )->ahora()->getTimestamp() + 100 );

		self::assertTrue( ( new ProveedorGoogleTrends( new RelojFijo() ) )->circuitoAbierto() );
	}

	public function test_circuito_cerrado_cuando_no_hay_enfriamiento_activo(): void {
		Functions\when( 'get_option' )->justReturn( 0 );

		self::assertFalse( ( new ProveedorGoogleTrends( new RelojFijo() ) )->circuitoAbierto() );
	}
}
