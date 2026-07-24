<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

/**
 * Único punto del plugin que llama `wp_insert_comment` (CLAUDE.md § Ley de
 * Arquitectura, mismo principio que `CreadorBorrador` con `wp_insert_post`).
 * Autor invitado (`comment_author`, sin `user_id`) — decisión del
 * propietario, 2026-07-23: los periodistas no tienen cuenta WP real.
 * `comment_approved = 1`: el editor ya aprobó el borrador en la Sala de
 * Comentarios, ponerlo en moderación de nuevo sería redundante.
 */
final class PublicadorComentario implements PublicadorComentarioInterface {

	public function publicar( int $postId, int $comentarioPadreId, string $autorNombre, string $contenido ): int {
		$comentarioId = wp_insert_comment(
			array(
				'comment_post_ID'      => $postId,
				'comment_parent'       => $comentarioPadreId,
				'comment_author'       => $autorNombre,
				'comment_content'      => $contenido,
				'comment_type'         => 'comment',
				'comment_approved'     => 1,
				'comment_author_email' => '',
				'comment_author_url'   => '',
			)
		);

		if ( false === $comentarioId ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
			throw new PublicacionComentarioException( "No se pudo publicar la respuesta en el post {$postId}." );
		}

		return $comentarioId;
	}
}
