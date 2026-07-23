<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * Plugin SEO detectado en el sitio (Libro Cap. 6.3: "si el sitio ya usa
 * Rank Math o Yoast, PLUMA escribe en sus campos en lugar de duplicar la
 * capa SEO").
 */
enum TipoPluginSeo: string {

	case Ninguno  = 'ninguno';
	case Yoast    = 'yoast';
	case RankMath = 'rank_math';
}
