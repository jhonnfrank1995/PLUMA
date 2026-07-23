<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Taxonomia\ResultadoTaxonomia;

interface PublicadorInterface {

	/**
	 * Paso 5 de la anatomía de una ejecución (Libro Cap. 9.3): convierte el
	 * borrador ya creado por `CreadorBorradorInterface` en post publicado,
	 * con su capa SEO y taxonomía reales.
	 *
	 * @throws PublicacionException
	 */
	public function publicar( int $postId, MetadatosSeo $metadatos, TipoPluginSeo $plugin, ResultadoTaxonomia $taxonomia ): void;
}
