<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Proveedores;

use Brain\Monkey\Functions;
use Pluma\Proveedores\ProveedorSearchConsole;
use Pluma\Proveedores\ProveedorSearchConsoleException;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;
use WP_Error;

/**
 * Verificado contra la documentación oficial de Google (identity/protocols/
 * oauth2/web-server y webmaster-tools/v1): endpoints, parámetros y forma de
 * la respuesta reales — la suite jamás llama a la API real (GOVERNANCE
 * §4.4/pl-proveedor-ia §5).
 *
 * @covers \Pluma\Proveedores\ProveedorSearchConsole
 */
final class ProveedorSearchConsoleTest extends CasoDePruebaUnitario {

	private function sinCircuitoAbierto(): void {
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'update_option' )->justReturn( true );
	}

	public function test_url_autorizacion_incluye_los_parametros_reales_de_google(): void {
		Functions\expect( 'add_query_arg' )
			->once()
			->with(
				array(
					'client_id'     => 'id-de-prueba',
					'redirect_uri'  => rawurlencode( 'https://sitio.test/wp-json/pluma/v1/search-console/callback' ),
					'response_type' => 'code',
					'scope'         => rawurlencode( 'https://www.googleapis.com/auth/webmasters.readonly' ),
					'access_type'   => 'offline',
					'prompt'        => 'consent',
					'state'         => rawurlencode( 'estado-de-prueba' ),
				),
				'https://accounts.google.com/o/oauth2/v2/auth'
			)
			->andReturn( 'https://accounts.google.com/o/oauth2/v2/auth?...' );

		$url = ( new ProveedorSearchConsole( new RelojFijo() ) )->urlAutorizacion(
			'id-de-prueba',
			'https://sitio.test/wp-json/pluma/v1/search-console/callback',
			'estado-de-prueba'
		);

		self::assertSame( 'https://accounts.google.com/o/oauth2/v2/auth?...', $url );
	}

	public function test_intercambiar_codigo_devuelve_los_tokens_reales(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_remote_post' )->justReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- fixture de prueba, no codigo de produccion.
					array(
						'access_token'  => 'access-de-prueba',
						'refresh_token' => 'refresh-de-prueba',
						'expires_in'    => 3600,
					)
				),
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static fn ( array $respuesta ): string => $respuesta['body']
		);

		$tokens = ( new ProveedorSearchConsole( new RelojFijo() ) )->intercambiarCodigo(
			'id-de-prueba',
			'secreto-de-prueba',
			'codigo-de-prueba',
			'https://sitio.test/wp-json/pluma/v1/search-console/callback'
		);

		self::assertSame( 'access-de-prueba', $tokens->accessToken );
		self::assertSame( 'refresh-de-prueba', $tokens->refreshToken );
		self::assertSame( 3600, $tokens->expiraEnSegundos );
	}

	public function test_refrescar_access_token_conserva_refresh_token_null_cuando_google_no_lo_reemite(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_remote_post' )->justReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- fixture de prueba, no codigo de produccion.
					array(
						'access_token' => 'access-nuevo',
						'expires_in'   => 1800,
					)
				),
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static fn ( array $respuesta ): string => $respuesta['body']
		);

		$tokens = ( new ProveedorSearchConsole( new RelojFijo() ) )->refrescarAccessToken(
			'id-de-prueba',
			'secreto-de-prueba',
			'refresh-existente'
		);

		self::assertSame( 'access-nuevo', $tokens->accessToken );
		self::assertNull( $tokens->refreshToken );
		self::assertSame( 1800, $tokens->expiraEnSegundos );
	}

	public function test_intercambiar_codigo_lanza_excepcion_si_google_rechaza(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_remote_post' )->justReturn( array( 'response' => array( 'code' => 400 ) ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 400 );

		$this->expectException( ProveedorSearchConsoleException::class );

		( new ProveedorSearchConsole( new RelojFijo() ) )->intercambiarCodigo( 'id', 'secreto', 'codigo-invalido', 'https://sitio.test/callback' );
	}

	public function test_intercambiar_codigo_lanza_excepcion_si_wp_remote_post_devuelve_wp_error(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_remote_post' )->justReturn( new WP_Error( 'http_request_failed', 'Timeout' ) );
		Functions\when( 'is_wp_error' )->alias( static fn ( $valor ): bool => $valor instanceof WP_Error );

		$this->expectException( ProveedorSearchConsoleException::class );

		( new ProveedorSearchConsole( new RelojFijo() ) )->intercambiarCodigo( 'id', 'secreto', 'codigo', 'https://sitio.test/callback' );
	}

	public function test_listar_sitios_devuelve_los_sitios_reales(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_remote_get' )->justReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- fixture de prueba, no codigo de produccion.
					array(
						'siteEntry' => array(
							array(
								'siteUrl'         => 'https://sitio.test/',
								'permissionLevel' => 'siteOwner',
							),
						),
					)
				),
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static fn ( array $respuesta ): string => $respuesta['body']
		);

		$sitios = ( new ProveedorSearchConsole( new RelojFijo() ) )->listarSitios( 'access-de-prueba' );

		self::assertSame(
			array(
				array(
					'siteUrl'         => 'https://sitio.test/',
					'permissionLevel' => 'siteOwner',
				),
			),
			$sitios
		);
	}

	public function test_consultar_analitica_devuelve_las_filas_reales(): void {
		$this->sinCircuitoAbierto();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'wp_remote_post' )->justReturn(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- fixture de prueba, no codigo de produccion.
					array(
						'rows' => array(
							array(
								'keys'        => array( 'https://sitio.test/una-pieza/', 'elecciones 2026' ),
								'clicks'      => 12.0,
								'impressions' => 340.0,
								'ctr'         => 0.0353,
								'position'    => 8.4,
							),
						),
					)
				),
			)
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static fn ( array $respuesta ): string => $respuesta['body']
		);

		$filas = ( new ProveedorSearchConsole( new RelojFijo() ) )->consultarAnalitica(
			'access-de-prueba',
			'https://sitio.test/',
			( new RelojFijo() )->ahora()->modify( '-28 days' ),
			( new RelojFijo() )->ahora()
		);

		self::assertCount( 1, $filas );
		self::assertSame( 'https://sitio.test/una-pieza/', $filas[0]->pagina );
		self::assertSame( 'elecciones 2026', $filas[0]->consulta );
		self::assertSame( 12, $filas[0]->clics );
		self::assertSame( 340, $filas[0]->impresiones );
		self::assertSame( 0.0353, $filas[0]->ctr );
		self::assertSame( 8.4, $filas[0]->posicion );
	}

	public function test_circuito_abierto_impide_una_nueva_llamada_de_red(): void {
		Functions\when( 'get_option' )->alias(
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInTernaryCondition.FoundInTernaryCondition -- falso positivo: es una arrow function con `=>`, no una asignación.
			static fn ( string $opcion, $defecto = false ) => 'pluma_proveedor_search_console_abierto_hasta' === $opcion
				? ( new RelojFijo() )->ahora()->getTimestamp() + 100
				: $defecto
		);

		Functions\expect( 'wp_remote_get' )->never();

		$this->expectException( ProveedorSearchConsoleException::class );
		$this->expectExceptionMessageMatches( '/[Cc]ircuito abierto/' );

		( new ProveedorSearchConsole( new RelojFijo() ) )->listarSitios( 'access-de-prueba' );
	}

	public function test_circuito_abierto_expone_el_mismo_estado_para_la_sala_de_maquinas(): void {
		Functions\when( 'get_option' )->justReturn( ( new RelojFijo() )->ahora()->getTimestamp() + 100 );

		self::assertTrue( ( new ProveedorSearchConsole( new RelojFijo() ) )->circuitoAbierto() );
	}

	public function test_circuito_cerrado_cuando_no_hay_enfriamiento_activo(): void {
		Functions\when( 'get_option' )->justReturn( 0 );

		self::assertFalse( ( new ProveedorSearchConsole( new RelojFijo() ) )->circuitoAbierto() );
	}
}
