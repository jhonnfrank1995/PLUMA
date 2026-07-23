<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Redaccion\BorradorMecanico;

/**
 * Único punto del plugin que llama `wp_insert_post` (CLAUDE.md § Ley de
 * Arquitectura). Etapa 1 = modo Piloto siempre: el post nace como borrador,
 * jamás publicado directamente (Libro Cap. 2.4).
 */
final class CreadorBorrador implements CreadorBorradorInterface {

	/**
	 * @throws CreacionBorradorException
	 */
	public function crear( BorradorMecanico $borrador ): int {
		$postId = wp_insert_post(
			array(
				'post_title'   => $borrador->titulo,
				'post_content' => $borrador->cuerpoHtml,
				'post_status'  => 'draft',
				'post_type'    => 'post',
			),
			true
		);

		if ( is_wp_error( $postId ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
			throw new CreacionBorradorException( $postId->get_error_message() );
		}

		return $postId;
	}
}
