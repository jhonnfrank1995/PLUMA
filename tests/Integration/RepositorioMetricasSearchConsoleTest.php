<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Datos\RepositorioMetricasSearchConsole;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Kernel\Activador;
use Pluma\Kernel\RelojSistema;
use Pluma\Proveedores\FilaAnaliticaSearchConsole;
use WP_UnitTestCase;

/**
 * Repositorio `pluma_metricas_search_console` (Libro Cap. 6.4) contra
 * WordPress real: `url_to_postid()` (WP core) resuelve la página real de
 * Search Console de vuelta a la Pieza que la publicó.
 *
 * @covers \Pluma\Datos\RepositorioMetricasSearchConsole
 */
final class RepositorioMetricasSearchConsoleTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activador::activar( new RelojSistema(), '0.10.0' );
	}

	public function test_guardar_lote_resuelve_la_pieza_real_y_una_segunda_sincronizacion_actualiza_en_vez_de_duplicar(): void {
		global $wpdb;
		$piezas = new RepositorioPiezas( $wpdb );
		$repo   = new RepositorioMetricasSearchConsole( $wpdb, $piezas );
		$reloj  = new RelojSistema();

		$postId  = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$piezaId = $piezas->crear( 1, $reloj->ahora() );
		$piezas->actualizarPostId( $piezaId, $postId, $reloj->ahora() );

		$permalink = get_permalink( $postId );
		self::assertIsString( $permalink );

		$guardadas = $repo->guardarLote(
			array( new FilaAnaliticaSearchConsole( $permalink, 'elecciones 2026', 10, 200, 0.05, 6.2 ) ),
			$reloj->ahora()
		);
		self::assertSame( 1, $guardadas );

		$recientes = $repo->obtenerRecientes( 10 );
		self::assertCount( 1, $recientes );
		self::assertSame( $postId, $recientes[0]['postId'] );
		self::assertSame( $piezaId, $recientes[0]['piezaId'] );
		self::assertSame( 'elecciones 2026', $recientes[0]['consulta'] );
		self::assertSame( 10, $recientes[0]['clics'] );

		$repo->guardarLote(
			array( new FilaAnaliticaSearchConsole( $permalink, 'elecciones 2026', 15, 250, 0.06, 5.9 ) ),
			$reloj->ahora()
		);

		$recientesTrasSegundaSincronizacion = $repo->obtenerRecientes( 10 );
		self::assertCount( 1, $recientesTrasSegundaSincronizacion );
		self::assertSame( 15, $recientesTrasSegundaSincronizacion[0]['clics'] );
	}

	public function test_guardar_lote_deja_pieza_id_nulo_cuando_la_url_no_pertenece_a_ninguna_pieza(): void {
		global $wpdb;
		$piezas = new RepositorioPiezas( $wpdb );
		$repo   = new RepositorioMetricasSearchConsole( $wpdb, $piezas );
		$reloj  = new RelojSistema();

		$postId    = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$permalink = get_permalink( $postId );
		self::assertIsString( $permalink );

		$repo->guardarLote(
			array( new FilaAnaliticaSearchConsole( $permalink, 'consulta ajena ' . uniqid(), 3, 40, 0.075, 12.1 ) ),
			$reloj->ahora()
		);

		$recientes = $repo->obtenerRecientes( 20 );
		$fila      = null;

		foreach ( $recientes as $candidata ) {
			if ( str_starts_with( $candidata['consulta'], 'consulta ajena' ) ) {
				$fila = $candidata;
			}
		}

		self::assertNotNull( $fila );
		self::assertNull( $fila['piezaId'] );
	}

	public function test_guardar_lote_ignora_filas_cuya_pagina_no_resuelve_a_ningun_post_real(): void {
		global $wpdb;
		$piezas = new RepositorioPiezas( $wpdb );
		$repo   = new RepositorioMetricasSearchConsole( $wpdb, $piezas );
		$reloj  = new RelojSistema();

		$guardadas = $repo->guardarLote(
			array( new FilaAnaliticaSearchConsole( 'https://sitio-que-no-existe.invalid/nada/', 'consulta', 1, 1, 0.1, 20.0 ) ),
			$reloj->ahora()
		);

		self::assertSame( 0, $guardadas );
	}
}
