<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Pluma\Redaccion\Tono;
use Pluma\Seo\TipoEsquemaArticulo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 6.2 — la distinción NewsArticle/OpinionNewsArticle/
 * AnalysisNewsArticle deriva directamente del tono dominante ya decidido
 * por `Pluma\Redaccion\DecisionEditorial`.
 *
 * @covers \Pluma\Seo\TipoEsquemaArticulo
 */
final class TipoEsquemaArticuloTest extends CasoDePruebaUnitario {

	public function test_tono_opinion_produce_opinion_news_article(): void {
		self::assertSame( TipoEsquemaArticulo::OpinionNewsArticle, TipoEsquemaArticulo::desdeTono( Tono::Opinion ) );
	}

	public function test_tono_analitico_produce_analysis_news_article(): void {
		self::assertSame( TipoEsquemaArticulo::AnalysisNewsArticle, TipoEsquemaArticulo::desdeTono( Tono::Analitico ) );
	}

	/**
	 * @return iterable<string, array{0: Tono}>
	 */
	public static function tonosDeCoberturaEstandar(): iterable {
		yield 'critico' => array( Tono::Critico );
		yield 'informativo_empatico' => array( Tono::InformativoEmpatico );
		yield 'humoristico' => array( Tono::Humoristico );
		yield 'persuasivo' => array( Tono::Persuasivo );
	}

	/**
	 * @dataProvider tonosDeCoberturaEstandar
	 */
	public function test_cualquier_otro_tono_produce_news_article_estandar( Tono $tono ): void {
		self::assertSame( TipoEsquemaArticulo::NewsArticle, TipoEsquemaArticulo::desdeTono( $tono ) );
	}
}
