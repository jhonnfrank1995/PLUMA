<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Brain\Monkey\Functions;
use Pluma\Seo\DatosSeo;
use Pluma\Seo\EnlaceInterno;
use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\PalabrasClave;
use Pluma\Seo\TipoEsquemaArticulo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * `RepositorioPiezas::actualizarDatosSeo()` persiste `DatosSeo` completo
 * como JSON — si el ida-y-vuelta pierde un campo, la Sala de Revisión
 * mostraría un diagnóstico SEO incompleto sin que ningún error lo delate.
 *
 * @covers \Pluma\Seo\DatosSeo
 * @covers \Pluma\Seo\PalabrasClave
 * @covers \Pluma\Seo\MetadatosSeo
 * @covers \Pluma\Seo\EnlaceInterno
 */
final class DatosSeoTest extends CasoDePruebaUnitario {

	private function datos(): DatosSeo {
		return new DatosSeo(
			new PalabrasClave( 'reforma pensional', array( 'gobierno', 'aportes' ) ),
			new MetadatosSeo( 'Titulo SEO', 'Meta descripción' ),
			TipoEsquemaArticulo::OpinionNewsArticle,
			TipoPluginSeo::RankMath,
			array( new EnlaceInterno( 7, 'https://example.com/7', 'Pieza relacionada' ) ),
			true
		);
	}

	public function test_ida_y_vuelta_por_array_preserva_todos_los_campos(): void {
		$original     = $this->datos();
		$reconstruida = DatosSeo::desdeArray( $original->aArray() );

		self::assertSame( $original->palabrasClave->principal, $reconstruida->palabrasClave->principal );
		self::assertSame( $original->palabrasClave->secundarias, $reconstruida->palabrasClave->secundarias );
		self::assertSame( $original->metadatos->tituloSeo, $reconstruida->metadatos->tituloSeo );
		self::assertSame( $original->tipoEsquema, $reconstruida->tipoEsquema );
		self::assertSame( $original->pluginDetectado, $reconstruida->pluginDetectado );
		self::assertCount( 1, $reconstruida->enlacesInternos );
		self::assertSame( 7, $reconstruida->enlacesInternos[0]->postId );
		self::assertTrue( $reconstruida->canibalizacionDetectada );
	}

	public function test_ida_y_vuelta_preserva_una_lista_vacia_de_enlaces(): void {
		$original = new DatosSeo(
			new PalabrasClave( 'x', array() ),
			new MetadatosSeo( 't', 'd' ),
			TipoEsquemaArticulo::NewsArticle,
			TipoPluginSeo::Ninguno,
			array(),
			false
		);

		$reconstruida = DatosSeo::desdeArray( $original->aArray() );

		self::assertSame( array(), $reconstruida->enlacesInternos );
	}

	public function test_el_json_encode_de_aArray_produce_un_string_valido(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$json = wp_json_encode( $this->datos()->aArray() );

		self::assertIsString( $json );
		self::assertSame( $this->datos()->aArray(), json_decode( $json, true ) );
	}
}
