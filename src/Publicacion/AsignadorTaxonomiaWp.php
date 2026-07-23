<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Taxonomia\EtiquetaAsignada;
use Pluma\Taxonomia\ResultadoTaxonomia;

/**
 * Aplica el `ResultadoTaxonomia` de `Pluma\Taxonomia\Taxonomo` sobre el post
 * real de WordPress (Libro Cap. 7.1): categorías y etiquetas de PLUMA SON
 * las taxonomías nativas `category`/`post_tag` — no hay una taxonomía
 * propia paralela.
 *
 * Categoría: `category` es jerárquica, así que WordPress exige IDs, no
 * nombres (ambigüedad de nombres repetidos en distintas ramas) — se
 * resuelve el nombre a `term_id` primero; si no existe como categoría real
 * (el editor aún no la creó en wp-admin), la pieza queda sin categoría en
 * vez de inventar una — el Taxónomo nunca crea categorías.
 *
 * Etiquetas: `post_tag` no es jerárquica, así que `wp_set_post_terms()` con
 * el NOMBRE reutiliza el término existente o lo crea si no existe
 * (verificado contra el código fuente de `wp_set_object_terms()` en
 * `wp-includes/taxonomy.php`: `term_exists()` primero, `wp_insert_term()`
 * si no hay coincidencia). Las etiquetas nuevas en cuarentena (Cap. 7.2,
 * punto 3) se marcan con post meta propio para que un futuro consumidor
 * (plantilla de archivo / integración SEO) decida no indexar esa página de
 * archivo hasta que acumule 3+ piezas — ver `docs/deuda.md`.
 */
final class AsignadorTaxonomiaWp {

	private const META_ETIQUETA_EN_CUARENTENA = '_pluma_etiqueta_en_cuarentena';

	public function asignar( int $postId, ResultadoTaxonomia $resultado ): void {
		if ( null !== $resultado->categoriaAsignada ) {
			$this->asignarCategoria( $postId, $resultado->categoriaAsignada );
		}

		if ( array() !== $resultado->etiquetas ) {
			$this->asignarEtiquetas( $postId, $resultado->etiquetas );
		}
	}

	private function asignarCategoria( int $postId, string $nombreCategoria ): void {
		$termino = get_term_by( 'name', $nombreCategoria, 'category' );

		if ( false === $termino ) {
			return;
		}

		wp_set_post_categories( $postId, array( $termino->term_id ) );
	}

	/**
	 * @param list<EtiquetaAsignada> $etiquetas
	 */
	private function asignarEtiquetas( int $postId, array $etiquetas ): void {
		$nombres = array_map( static fn ( EtiquetaAsignada $e ): string => $e->nombre, $etiquetas );

		wp_set_post_terms( $postId, $nombres, 'post_tag', true );

		foreach ( $etiquetas as $etiqueta ) {
			if ( ! $etiqueta->enCuarentena ) {
				continue;
			}

			$termino = get_term_by( 'name', $etiqueta->nombre, 'post_tag' );

			if ( false !== $termino ) {
				update_term_meta( $termino->term_id, self::META_ETIQUETA_EN_CUARENTENA, true );
			}
		}
	}
}
