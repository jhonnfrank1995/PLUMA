<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\TipoPluginSeo;

/**
 * Escribe el "doble titular" y la meta descripción en los campos reales del
 * plugin SEO detectado por `Pluma\Seo\MotorSeo` en el momento de optimizar
 * (Libro Cap. 6.3: "si el sitio ya usa Rank Math o Yoast, PLUMA escribe en
 * sus campos en lugar de duplicar la capa SEO"). Meta keys verificadas
 * contra el código fuente oficial de ambos plugins (ver
 * `Pluma\Seo\DetectorPluginSeo`). Se reutiliza `$plugin` ya detectado y
 * persistido en `DatosSeo` en vez de re-detectar en este punto — evita una
 * dependencia de capa hacia `Pluma\Seo` (no adyacente a `Pluma\Publicacion`)
 * por algo que ya se resolvió antes.
 */
final class EscritorCamposSeo {

	private const META_TITULO_PROPIO          = '_pluma_seo_titulo';
	private const META_METADESCRIPCION_PROPIO = '_pluma_seo_metadescripcion';

	public function escribir( int $postId, MetadatosSeo $metadatos, TipoPluginSeo $plugin ): void {
		match ( $plugin ) {
			TipoPluginSeo::Yoast => $this->escribirYoast( $postId, $metadatos ),
			TipoPluginSeo::RankMath => $this->escribirRankMath( $postId, $metadatos ),
			TipoPluginSeo::Ninguno => $this->escribirPropio( $postId, $metadatos ),
		};
	}

	private function escribirYoast( int $postId, MetadatosSeo $metadatos ): void {
		update_post_meta( $postId, '_yoast_wpseo_title', $metadatos->tituloSeo );
		update_post_meta( $postId, '_yoast_wpseo_metadesc', $metadatos->metaDescripcion );
	}

	private function escribirRankMath( int $postId, MetadatosSeo $metadatos ): void {
		update_post_meta( $postId, 'rank_math_title', $metadatos->tituloSeo );
		update_post_meta( $postId, 'rank_math_description', $metadatos->metaDescripcion );
	}

	private function escribirPropio( int $postId, MetadatosSeo $metadatos ): void {
		update_post_meta( $postId, self::META_TITULO_PROPIO, $metadatos->tituloSeo );
		update_post_meta( $postId, self::META_METADESCRIPCION_PROPIO, $metadatos->metaDescripcion );
	}
}
