<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use DateTimeImmutable;
use Pluma\Datos\RepositorioMemoriaEditorialInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Investigacion\Expediente;
use Pluma\Redaccion\CandidatoTesis;
use Pluma\Redaccion\ClasificacionNoticia;
use Pluma\Redaccion\EsqueletoPieza;
use Pluma\Redaccion\FichaDecisionEditorial;
use Pluma\Redaccion\NovedadNoticia;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Seo\AuditorCanibalizacion;
use Pluma\Seo\DetectorPluginSeo;
use Pluma\Seo\EnlazadorInterno;
use Pluma\Seo\ExtractorPalabrasClave;
use Pluma\Seo\GeneradorMetadatosSeo;
use Pluma\Seo\MotorSeo;
use Pluma\Seo\TipoEsquemaArticulo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\ProveedorLenguajeFalso;

/**
 * Único punto de entrada de `Pluma\Seo` (Libro Cap. 6): orquesta las cinco
 * piezas de cómputo en una sola llamada. `ExtractorPalabrasClave`,
 * `GeneradorMetadatosSeo`, `DetectorPluginSeo`, `EnlazadorInterno` y
 * `AuditorCanibalizacion` son clases `final` (mismo criterio que
 * `Pluma\Compuertas\EvaluadorCompuertas`) — se instancian reales aquí en vez
 * de doblarse, y solo los repositorios (interfaces) se mockean.
 *
 * @covers \Pluma\Seo\MotorSeo
 */
final class MotorSeoTest extends CasoDePruebaUnitario {

	private function ficha(): FichaDecisionEditorial {
		return new FichaDecisionEditorial(
			5,
			1,
			new ClasificacionNoticia( 'economia', 30, 'x', NovedadNoticia::Primicia, 50, TipoNoticia::DatoEconomico ),
			array( new CandidatoTesis( 'la tesis elegida', 80.0, 80.0, 80.0, 80.0 ) ),
			0,
			Tono::Analitico,
			Tono::Persuasivo,
			new EsqueletoPieza( 'gancho', 'hechos', array( 'm1' ), 'contra', 'remate' ),
			new DateTimeImmutable( '2026-07-22T12:00:00+00:00' )
		);
	}

	private function motor( RepositorioMemoriaEditorialInterface $repoMemoria, RepositorioPiezasInterface $repoPiezas ): MotorSeo {
		return new MotorSeo(
			new ExtractorPalabrasClave(),
			new GeneradorMetadatosSeo( new ProveedorLenguajeFalso( '{"tituloSeo": "Titulo SEO", "metaDescripcion": "Meta descripción"}' ) ),
			new DetectorPluginSeo(),
			new EnlazadorInterno( $repoMemoria, $repoPiezas ),
			new AuditorCanibalizacion( $repoPiezas )
		);
	}

	public function test_optimizar_orquesta_las_cinco_piezas_de_computo(): void {
		$expediente = new Expediente( 'reforma pensional', array() );

		$repoMemoria = $this->createMock( RepositorioMemoriaEditorialInterface::class );
		$repoMemoria->method( 'obtenerPosturasPorTema' )->with( 5, 'economia' )->willReturn( array() );

		$repoPiezas = $this->createMock( RepositorioPiezasInterface::class );
		$repoPiezas->method( 'existePiezaPublicadaConKeyword' )->with( 'reforma pensional', 99 )->willReturn( true );

		$datos = $this->motor( $repoMemoria, $repoPiezas )->optimizar( 99, $expediente, $this->ficha(), 'Titular editorial' );

		self::assertSame( 'reforma pensional', $datos->palabrasClave->principal );
		self::assertSame( 'Titulo SEO', $datos->metadatos->tituloSeo );
		self::assertSame( TipoEsquemaArticulo::AnalysisNewsArticle, $datos->tipoEsquema );
		self::assertSame( TipoPluginSeo::Ninguno, $datos->pluginDetectado );
		self::assertSame( array(), $datos->enlacesInternos );
		self::assertTrue( $datos->canibalizacionDetectada );
	}
}
