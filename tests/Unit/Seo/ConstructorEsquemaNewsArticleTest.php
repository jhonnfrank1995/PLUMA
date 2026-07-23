<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use DateTimeImmutable;
use Pluma\Seo\ConstructorEsquemaNewsArticle;
use Pluma\Seo\TipoEsquemaArticulo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Propiedades verificadas contra la guía oficial de Google para
 * Article/NewsArticle y contra las propiedades estándar de `Article` en
 * schema.org (ver docblock de la clase).
 *
 * @covers \Pluma\Seo\ConstructorEsquemaNewsArticle
 */
final class ConstructorEsquemaNewsArticleTest extends CasoDePruebaUnitario {

	private function fecha( string $iso ): DateTimeImmutable {
		return new DateTimeImmutable( $iso );
	}

	public function test_construye_el_documento_completo_con_autor_y_logo(): void {
		$documento = ( new ConstructorEsquemaNewsArticle() )->construir(
			TipoEsquemaArticulo::NewsArticle,
			'Titular de la pieza',
			array( 'https://example.com/foto.jpg' ),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			$this->fecha( '2026-07-23T09:00:00+00:00' ),
			'Valentina Ruiz',
			'https://example.com/autor/valentina-ruiz',
			'Mi Sitio',
			'https://example.com/logo.png',
			'https://example.com/pieza'
		);

		self::assertSame( 'https://schema.org', $documento['@context'] );
		self::assertSame( 'NewsArticle', $documento['@type'] );
		self::assertSame( 'Titular de la pieza', $documento['headline'] );
		self::assertSame( '2026-07-22T10:00:00+00:00', $documento['datePublished'] );
		self::assertSame( '2026-07-23T09:00:00+00:00', $documento['dateModified'] );
		self::assertSame( array( 'https://example.com/foto.jpg' ), $documento['image'] );
		self::assertSame(
			array(
				'@type' => 'Person',
				'name'  => 'Valentina Ruiz',
				'url'   => 'https://example.com/autor/valentina-ruiz',
			),
			$documento['author']
		);
		self::assertSame( 'Mi Sitio', $documento['publisher']['name'] );
		self::assertSame( 'https://example.com/logo.png', $documento['publisher']['logo']['url'] );
		self::assertSame( 'https://example.com/pieza', $documento['mainEntityOfPage']['@id'] );
	}

	public function test_usa_opinion_news_article_cuando_se_indica(): void {
		$documento = ( new ConstructorEsquemaNewsArticle() )->construir(
			TipoEsquemaArticulo::OpinionNewsArticle,
			'Titular',
			array(),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			'Autor',
			null,
			'Sitio',
			null,
			'https://example.com/pieza'
		);

		self::assertSame( 'OpinionNewsArticle', $documento['@type'] );
	}

	public function test_omite_url_de_autor_cuando_no_hay_pagina_de_perfil(): void {
		$documento = ( new ConstructorEsquemaNewsArticle() )->construir(
			TipoEsquemaArticulo::NewsArticle,
			'Titular',
			array(),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			'Autor sin perfil',
			null,
			'Sitio',
			null,
			'https://example.com/pieza'
		);

		self::assertArrayNotHasKey( 'url', $documento['author'] );
	}

	public function test_omite_logo_del_editor_cuando_el_sitio_no_tiene_uno(): void {
		$documento = ( new ConstructorEsquemaNewsArticle() )->construir(
			TipoEsquemaArticulo::NewsArticle,
			'Titular',
			array(),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			'Autor',
			null,
			'Sitio sin logo',
			null,
			'https://example.com/pieza'
		);

		self::assertArrayNotHasKey( 'logo', $documento['publisher'] );
	}

	public function test_omite_la_propiedad_image_cuando_no_hay_imagenes(): void {
		$documento = ( new ConstructorEsquemaNewsArticle() )->construir(
			TipoEsquemaArticulo::NewsArticle,
			'Titular',
			array(),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			$this->fecha( '2026-07-22T10:00:00+00:00' ),
			'Autor',
			null,
			'Sitio',
			null,
			'https://example.com/pieza'
		);

		self::assertArrayNotHasKey( 'image', $documento );
	}
}
