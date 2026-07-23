<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Redaccion\Tono;

/**
 * Subtipo de datos estructurados schema.org (Libro Cap. 6.2: "NewsArticle
 * (o AnalysisNewsArticle/OpinionNewsArticle según el tipo de pieza — la
 * distinción existe en schema.org y casi nadie la usa: ventaja)"). El tono
 * dominante de `FichaDecisionEditorial` ya distingue exactamente esto: una
 * pieza de tono `Opinion` es una opinión declarada, una de tono `Analitico`
 * es un análisis; cualquier otro tono es cobertura noticiosa estándar.
 */
enum TipoEsquemaArticulo: string {

	case NewsArticle         = 'NewsArticle';
	case OpinionNewsArticle  = 'OpinionNewsArticle';
	case AnalysisNewsArticle = 'AnalysisNewsArticle';

	public static function desdeTono( Tono $tono ): self {
		return match ( $tono ) {
			Tono::Opinion => self::OpinionNewsArticle,
			Tono::Analitico => self::AnalysisNewsArticle,
			Tono::Critico, Tono::InformativoEmpatico, Tono::Humoristico, Tono::Persuasivo => self::NewsArticle,
		};
	}
}
