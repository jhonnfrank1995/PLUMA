<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

interface PublicadorComentarioInterface {

	/**
	 * Publica un comentario real (autor invitado, sin cuenta WP vinculada —
	 * decisión del propietario, 2026-07-23). Devuelve el id real del
	 * comentario publicado.
	 *
	 * @throws PublicacionComentarioException
	 */
	public function publicar( int $postId, int $comentarioPadreId, string $autorNombre, string $contenido ): int;
}
