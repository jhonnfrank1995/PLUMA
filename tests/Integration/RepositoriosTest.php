<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\TendenciaDetectada;
use WP_UnitTestCase;

/**
 * Repositorios `pluma_tendencias`/`pluma_piezas` contra tablas reales
 * (pl-testing: "Integración (wp-env): repositorios pluma_*").
 *
 * @covers \Pluma\Datos\RepositorioPiezas
 * @covers \Pluma\Datos\RepositorioTendencias
 */
final class RepositoriosTest extends WP_UnitTestCase {

	public function test_guardar_y_deduplicar_tendencias(): void {
		global $wpdb;
		$repo  = new RepositorioTendencias( $wpdb );
		$reloj = new RelojSistema();

		$tendencia = new TendenciaDetectada(
			'Una Tendencia',
			PuntuacionOportunidad::calcular( 70, 70 ),
			$reloj->ahora(),
			array(
				array(
					'titulo' => 'Artículo',
					'url'    => 'https://example.com/a',
					'fuente' => 'Example',
				),
			),
			'google_trends'
		);

		self::assertFalse( $repo->existePorTermino( 'una tendencia', 'google_trends' ) );

		$id = $repo->guardar( $tendencia, $reloj->ahora() );

		self::assertGreaterThan( 0, $id );
		self::assertTrue( $repo->existePorTermino( 'UNA TENDENCIA', 'google_trends' ) );

		$datos = $repo->obtenerPorId( $id );
		self::assertNotNull( $datos );
		self::assertSame( 'una tendencia', $datos['termino'] );
		self::assertSame( 'Example', $datos['articulosRelacionados'][0]['fuente'] );
	}

	public function test_crear_pieza_y_avanzarla_por_el_grafo_con_candado_optimista(): void {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'otra tendencia', PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);

		$piezaId = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		$pieza   = $repoPiezas->obtenerPorId( $piezaId );

		self::assertNotNull( $pieza );
		self::assertSame( EstadoPieza::Detectada, $pieza->estado );
		self::assertNull( $pieza->postId );

		// Candado optimista: la transición desde el estado REAL funciona...
		self::assertTrue( $repoPiezas->actualizarEstado( $piezaId, EstadoPieza::Detectada, EstadoPieza::EnInvestigacion, $reloj->ahora() ) );
		// ...pero reintentar la MISMA transición (estado ya avanzó) falla silenciosamente.
		self::assertFalse( $repoPiezas->actualizarEstado( $piezaId, EstadoPieza::Detectada, EstadoPieza::EnInvestigacion, $reloj->ahora() ) );

		$expediente = new Expediente(
			'otra tendencia',
			array(
				new HechoFuente( 'un hecho', 'https://example.com', $reloj->ahora(), NivelVerificacion::Atribuido ),
			)
		);
		self::assertTrue( $repoPiezas->actualizarExpediente( $piezaId, $expediente, $reloj->ahora() ) );
		self::assertTrue( $repoPiezas->actualizarPostId( $piezaId, 999, $reloj->ahora() ) );

		$piezaActualizada = $repoPiezas->obtenerPorId( $piezaId );
		self::assertSame( EstadoPieza::EnInvestigacion, $piezaActualizada->estado );
		self::assertSame( 999, $piezaActualizada->postId );
		self::assertNotNull( $piezaActualizada->expediente );
		self::assertSame( 'un hecho', $piezaActualizada->expediente->hechos[0]->extracto );

		$enDetectada = $repoPiezas->obtenerPorEstado( EstadoPieza::Detectada, 10 );
		self::assertNotContains( $piezaId, array_map( static fn ( $p ) => $p->id, $enDetectada ) );
	}
}
