<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Seo\MetadatosSeo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Taxonomia\ResultadoTaxonomia;

final class Publicador implements PublicadorInterface {

	public function __construct(
		private readonly EscritorCamposSeo $escritorSeo,
		private readonly AsignadorTaxonomiaWp $asignadorTaxonomia,
	) {
	}

	public function publicar( int $postId, MetadatosSeo $metadatos, TipoPluginSeo $plugin, ResultadoTaxonomia $taxonomia ): void {
		$resultado = wp_update_post(
			array(
				'ID'          => $postId,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $resultado ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
			throw new PublicacionException( $resultado->get_error_message() );
		}

		$this->escritorSeo->escribir( $postId, $metadatos, $plugin );
		$this->asignadorTaxonomia->asignar( $postId, $taxonomia );
	}
}
