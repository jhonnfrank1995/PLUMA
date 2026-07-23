<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Compuertas\DiagnosticoCalidad;
use Pluma\Compuertas\DiagnosticoOriginalidad;
use Pluma\Compuertas\DiagnosticoRiesgo;
use Pluma\Compuertas\ModoOperacion;
use Pluma\Compuertas\ResultadoEvaluacion;
use Pluma\Datos\RepositorioBitacora;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Kernel\RelojSistema;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Seo\DatosSeo;
use Pluma\Seo\EnlaceInterno;
use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\PalabrasClave;
use Pluma\Seo\TipoEsquemaArticulo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\TendenciaDetectada;
use Pluma\Taxonomia\EtiquetaAsignada;
use Pluma\Taxonomia\ResultadoTaxonomia;
use WP_UnitTestCase;

/**
 * Repositorios `pluma_tendencias`/`pluma_piezas` contra tablas reales
 * (pl-testing: "Integración (wp-env): repositorios pluma_*").
 *
 * @covers \Pluma\Datos\RepositorioPiezas
 * @covers \Pluma\Datos\RepositorioTendencias
 * @covers \Pluma\Datos\RepositorioBitacora
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

	public function test_persistir_y_rehidratar_el_resultado_de_compuertas(): void {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia compuertas', PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );

		$resultado = new ResultadoEvaluacion(
			false,
			true,
			array( 'riesgoDifamacion' ),
			ModoOperacion::Copiloto,
			new DiagnosticoCalidad( 82, 70, true, array() ),
			new DiagnosticoRiesgo( true, false, false, false, true, 'acusación sin atribución', false, null ),
			new DiagnosticoOriginalidad( false, false, 0.6, 0.4 )
		);

		self::assertTrue( $repoPiezas->actualizarResultadoCompuertas( $piezaId, $resultado, $reloj->ahora() ) );

		$piezaActualizada = $repoPiezas->obtenerPorId( $piezaId );
		self::assertNotNull( $piezaActualizada->resultadoCompuertas );
		self::assertSame( ModoOperacion::Copiloto, $piezaActualizada->resultadoCompuertas->modoEfectivo );
		self::assertTrue( $piezaActualizada->resultadoCompuertas->retenida );
		self::assertSame( 'acusación sin atribución', $piezaActualizada->resultadoCompuertas->riesgo->detalleDifamacion );
		self::assertSame( 82, $piezaActualizada->resultadoCompuertas->calidad->puntuacionTotal );
	}

	public function test_persistir_y_rehidratar_datos_seo_y_auditar_canibalizacion(): void {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$crearPieza = function () use ( $repoTendencias, $repoPiezas, $reloj ): int {
			$tendenciaId = $repoTendencias->guardar(
				new TendenciaDetectada( 'tendencia seo ' . uniqid(), PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
				$reloj->ahora()
			);

			return $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		};

		$piezaId = $crearPieza();

		$datosSeo = new DatosSeo(
			new PalabrasClave( 'reforma pensional', array( 'gobierno', 'aportes' ) ),
			new MetadatosSeo( 'Titulo SEO', 'Meta descripción' ),
			TipoEsquemaArticulo::AnalysisNewsArticle,
			TipoPluginSeo::RankMath,
			array( new EnlaceInterno( 7, 'https://example.com/7', 'Pieza relacionada' ) ),
			false
		);

		self::assertTrue( $repoPiezas->actualizarDatosSeo( $piezaId, $datosSeo, $reloj->ahora() ) );

		$piezaActualizada = $repoPiezas->obtenerPorId( $piezaId );
		self::assertNotNull( $piezaActualizada->datosSeo );
		self::assertSame( 'reforma pensional', $piezaActualizada->datosSeo->palabrasClave->principal );
		self::assertSame( TipoPluginSeo::RankMath, $piezaActualizada->datosSeo->pluginDetectado );
		self::assertSame( 7, $piezaActualizada->datosSeo->enlacesInternos[0]->postId );

		// Todavía no hay ninguna pieza PUBLICADA con esta keyword: sin canibalización.
		self::assertFalse( $repoPiezas->existePiezaPublicadaConKeyword( 'reforma pensional', 0 ) );

		// Otra pieza distinta, publicada, con la MISMA keyword principal: canibalización real.
		$otraPiezaId = $crearPieza();
		self::assertTrue( $repoPiezas->actualizarEstado( $otraPiezaId, EstadoPieza::Detectada, EstadoPieza::EnInvestigacion, $reloj->ahora() ) );
		$estadosIntermedios = array(
			EstadoPieza::Investigada,
			EstadoPieza::EnRedaccion,
			EstadoPieza::Redactada,
			EstadoPieza::Optimizada,
			EstadoPieza::EnRevision,
			EstadoPieza::Aprobada,
			EstadoPieza::Programada,
			EstadoPieza::Publicada,
		);
		$estadoAnterior     = EstadoPieza::EnInvestigacion;
		foreach ( $estadosIntermedios as $estadoSiguiente ) {
			self::assertTrue( $repoPiezas->actualizarEstado( $otraPiezaId, $estadoAnterior, $estadoSiguiente, $reloj->ahora() ) );
			$estadoAnterior = $estadoSiguiente;
		}
		self::assertTrue( $repoPiezas->actualizarDatosSeo( $otraPiezaId, $datosSeo, $reloj->ahora() ) );

		self::assertTrue( $repoPiezas->existePiezaPublicadaConKeyword( 'reforma pensional', $piezaId ) );
		// La propia pieza publicada se excluye de su propia auditoría.
		self::assertFalse( $repoPiezas->existePiezaPublicadaConKeyword( 'reforma pensional', $otraPiezaId ) );
	}

	public function test_persistir_y_rehidratar_el_resultado_de_taxonomia(): void {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia taxonomia', PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$piezaId     = $repoPiezas->crear( $tendenciaId, $reloj->ahora() );

		$resultadoTaxonomia = new ResultadoTaxonomia(
			'Economía',
			array( new EtiquetaAsignada( 1, 'Banco de la República', false, false ) )
		);

		self::assertTrue( $repoPiezas->actualizarResultadoTaxonomia( $piezaId, $resultadoTaxonomia, $reloj->ahora() ) );

		$piezaActualizada = $repoPiezas->obtenerPorId( $piezaId );
		self::assertNotNull( $piezaActualizada->resultadoTaxonomia );
		self::assertSame( 'Economía', $piezaActualizada->resultadoTaxonomia->categoriaAsignada );
		self::assertSame( 'Banco de la República', $piezaActualizada->resultadoTaxonomia->etiquetas[0]->nombre );
	}

	public function test_obtener_recientes_ordena_las_tendencias_por_puntuacion_total(): void {
		global $wpdb;
		$repo  = new RepositorioTendencias( $wpdb );
		$reloj = new RelojSistema();

		$repo->guardar(
			new TendenciaDetectada( 'tendencia baja', PuntuacionOportunidad::calcular( 10, 10 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$idAlta = $repo->guardar(
			new TendenciaDetectada( 'tendencia alta', PuntuacionOportunidad::calcular( 90, 90 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);
		$repo->guardar(
			new TendenciaDetectada( 'tendencia media', PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);

		$recientes = $repo->obtenerRecientes( 2 );

		self::assertCount( 2, $recientes );
		self::assertSame( $idAlta, $recientes[0]['id'] );
		self::assertSame( 'tendencia alta', $recientes[0]['termino'] );
		self::assertGreaterThan( $recientes[1]['puntuacionTotal'], $recientes[0]['puntuacionTotal'] );
	}

	public function test_contar_por_estado_devuelve_el_total_exacto_sin_limitarlo(): void {
		global $wpdb;
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$reloj          = new RelojSistema();

		$totalAntes = $repoPiezas->contarPorEstado( EstadoPieza::Detectada );

		for ( $i = 0; $i < 3; $i++ ) {
			$tendenciaId = $repoTendencias->guardar(
				new TendenciaDetectada( 'tendencia conteo ' . $i, PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
				$reloj->ahora()
			);
			$repoPiezas->crear( $tendenciaId, $reloj->ahora() );
		}

		self::assertSame( $totalAntes + 3, $repoPiezas->contarPorEstado( EstadoPieza::Detectada ) );
	}

	public function test_obtener_ultima_ejecucion_de_la_bitacora(): void {
		global $wpdb;
		$repo  = new RepositorioBitacora( $wpdb );
		$reloj = new RelojSistema();

		self::assertNull( $repo->obtenerUltima() );

		$primeraId = $repo->iniciarEjecucion( $reloj->ahora() );
		$repo->finalizarEjecucion( $primeraId, $reloj->ahora(), 2, array() );

		$segundaId = $repo->iniciarEjecucion( $reloj->ahora()->modify( '+1 minute' ) );
		$repo->finalizarEjecucion( $segundaId, $reloj->ahora()->modify( '+1 minute' ), 1, array( 'fallo de proveedor' ) );

		$ultima = $repo->obtenerUltima();

		self::assertNotNull( $ultima );
		self::assertSame( 1, $ultima['lotesProcesados'] );
		self::assertSame( array( 'fallo de proveedor' ), $ultima['errores'] );
		self::assertNotNull( $ultima['finalizadaEn'] );
	}
}
