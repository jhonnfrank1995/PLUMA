<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * Detección verificada contra el código fuente oficial de ambos plugins
 * (Libro Cap. 6.3): Yoast SEO define `WPSEO_VERSION` en `wp-seo-main.php`;
 * Rank Math define `RANK_MATH_VERSION` en `define_constants()` de
 * `rank-math.php`. Ninguno de los dos expone sus campos vía la REST API de
 * WordPress por defecto, así que la integración es directa contra postmeta,
 * no contra un endpoint público.
 *
 * Si ambos estuvieran activos a la vez (configuración de sitio inusual y no
 * soportada por ninguno de los dos plugins entre sí), Yoast tiene prioridad
 * por ser el más extendido; no hay guía del producto para desempatar de otra forma.
 */
final class DetectorPluginSeo {

	public function detectar(): TipoPluginSeo {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return TipoPluginSeo::Yoast;
		}

		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return TipoPluginSeo::RankMath;
		}

		return TipoPluginSeo::Ninguno;
	}
}
