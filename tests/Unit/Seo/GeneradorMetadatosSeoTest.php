<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Pluma\Seo\GeneradorMetadatosSeo;
use Pluma\Seo\SeoException;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\ProveedorLenguajeFalso;

/**
 * El "doble titular" (Libro Cap. 6.2): una sola llamada consolidada produce
 * titular SEO y meta descripción juntos.
 *
 * @covers \Pluma\Seo\GeneradorMetadatosSeo
 */
final class GeneradorMetadatosSeoTest extends CasoDePruebaUnitario {

	public function test_genera_titulo_seo_y_meta_descripcion(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tituloSeo": "Reforma pensional: lo que cambia en 2026", "metaDescripcion": "El gobierno confirma el nuevo esquema de aportes y quién queda exento."}'
		);

		$metadatos = ( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular editorial', 'La tesis', 'reforma pensional' );

		self::assertSame( 'Reforma pensional: lo que cambia en 2026', $metadatos->tituloSeo );
		self::assertSame( 'El gobierno confirma el nuevo esquema de aportes y quién queda exento.', $metadatos->metaDescripcion );
	}

	public function test_trunca_el_titulo_si_el_modelo_ignora_el_limite(): void {
		$tituloLargo = str_repeat( 'x', 80 );
		$proveedor   = new ProveedorLenguajeFalso(
			sprintf( '{"tituloSeo": "%s", "metaDescripcion": "desc"}', $tituloLargo )
		);

		$metadatos = ( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular', 'Tesis', 'keyword' );

		self::assertSame( 60, mb_strlen( $metadatos->tituloSeo ) );
		self::assertStringEndsWith( '…', $metadatos->tituloSeo );
	}

	public function test_trunca_la_meta_descripcion_si_el_modelo_ignora_el_limite(): void {
		$descripcionLarga = str_repeat( 'y', 200 );
		$proveedor        = new ProveedorLenguajeFalso(
			sprintf( '{"tituloSeo": "titulo", "metaDescripcion": "%s"}', $descripcionLarga )
		);

		$metadatos = ( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular', 'Tesis', 'keyword' );

		self::assertSame( 155, mb_strlen( $metadatos->metaDescripcion ) );
		self::assertStringEndsWith( '…', $metadatos->metaDescripcion );
	}

	public function test_lanza_excepcion_si_falta_un_campo_en_la_respuesta(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"tituloSeo": "solo esto"}' );

		$this->expectException( SeoException::class );

		( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular', 'Tesis', 'keyword' );
	}

	public function test_lanza_excepcion_si_la_respuesta_llego_truncada(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tituloSeo": "t", "metaDescripcion": "d"}',
			truncada: true
		);

		$this->expectException( SeoException::class );

		( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular', 'Tesis', 'keyword' );
	}

	public function test_lanza_excepcion_si_no_hay_json_reconocible(): void {
		$proveedor = new ProveedorLenguajeFalso( 'esto no es json' );

		$this->expectException( SeoException::class );

		( new GeneradorMetadatosSeo( $proveedor ) )->generar( 'Titular', 'Tesis', 'keyword' );
	}
}
