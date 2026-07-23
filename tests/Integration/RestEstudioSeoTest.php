<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestEstudioSeo;
use Pluma\Datos\RepositorioVocabulario;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Taxonomia\TipoVocabulario;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Estudio SEO y Taxonomía (Libro Cap. 10.2) contra WordPress real: la misma
 * capacidad `pluma_configurar_motor` de la Sala de Máquinas, canibalización
 * agregada sobre piezas publicadas reales y salud taxonómica sobre
 * `pluma_vocabulario` real.
 *
 * @covers \Pluma\Admin\RestEstudioSeo
 */
final class RestEstudioSeoTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activador::activar( new RelojSistema(), '0.9.0' );
	}

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestEstudioSeo::class )->registrar();
		do_action( 'rest_api_init' );
	}

	private function crearPiezaPublicada( string $keywordPrincipal, int $postId ): void {
		global $wpdb;
		$ahora = ( new RelojSistema() )->ahora()->format( 'Y-m-d H:i:s' );

		$wpdb->insert(
			$wpdb->prefix . 'pluma_piezas',
			array(
				'tendencia_id'      => 1,
				'estado'            => EstadoPieza::Publicada->value,
				'keyword_principal' => $keywordPrincipal,
				'post_id'           => $postId,
				'creada_en'         => $ahora,
				'actualizada_en'    => $ahora,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	public function test_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/seo/canibalizacion' ) );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_canibalizacion_agrupa_piezas_publicadas_con_la_misma_keyword(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$keyword = 'elecciones-2026-' . uniqid();
		$postA   = self::factory()->post->create( array( 'post_title' => 'Cobertura A' ) );
		$postB   = self::factory()->post->create( array( 'post_title' => 'Cobertura B' ) );

		$this->crearPiezaPublicada( $keyword, $postA );
		$this->crearPiezaPublicada( $keyword, $postB );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/seo/canibalizacion' ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );

		$grupo = null;
		foreach ( $datos as $candidato ) {
			if ( $candidato['keywordPrincipal'] === $keyword ) {
				$grupo = $candidato;
			}
		}

		self::assertNotNull( $grupo );
		self::assertCount( 2, $grupo['piezas'] );
		$titulos = array_column( $grupo['piezas'], 'titulo' );
		self::assertContains( 'Cobertura A', $titulos );
		self::assertContains( 'Cobertura B', $titulos );
	}

	public function test_canibalizacion_no_agrupa_una_keyword_usada_una_sola_vez(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$keyword = 'keyword-unica-' . uniqid();
		$post    = self::factory()->post->create();
		$this->crearPiezaPublicada( $keyword, $post );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/seo/canibalizacion' ) );
		$datos     = $respuesta->get_data();

		self::assertNotContains( $keyword, array_column( $datos, 'keywordPrincipal' ) );
	}

	public function test_vocabulario_separa_cuarentena_y_propone_fusiones_por_similitud(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		global $wpdb;
		$repo   = new RepositorioVocabulario( $wpdb );
		$reloj  = new RelojSistema();
		$sufijo = uniqid();

		$repo->crear( TipoVocabulario::Etiqueta, 'En cuarentena ' . $sufijo, 'en-cuarentena-' . $sufijo, array(), true, $reloj->ahora() );
		$repo->crear( TipoVocabulario::Etiqueta, 'elecciones 2026 ' . $sufijo, 'elecciones-2026-' . $sufijo, array(), false, $reloj->ahora() );
		$repo->crear( TipoVocabulario::Etiqueta, 'eleccion 2026 ' . $sufijo, 'eleccion-2026-' . $sufijo, array(), false, $reloj->ahora() );

		$this->registrarRutas();

		$respuesta = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/pluma/v1/seo/vocabulario' ) );
		$datos     = $respuesta->get_data();

		self::assertSame( 200, $respuesta->get_status() );

		$nombresCuarentena = array_column( $datos['cuarentena'], 'nombre' );
		self::assertContains( 'En cuarentena ' . $sufijo, $nombresCuarentena );

		$encontrada = false;
		foreach ( $datos['propuestasFusion'] as $propuesta ) {
			if ( str_contains( $propuesta['nombreA'], $sufijo ) && str_contains( $propuesta['nombreB'], $sufijo ) ) {
				$encontrada = true;
				self::assertGreaterThanOrEqual( 85.0, $propuesta['similitud'] );
			}
		}

		self::assertTrue( $encontrada );
	}
}
