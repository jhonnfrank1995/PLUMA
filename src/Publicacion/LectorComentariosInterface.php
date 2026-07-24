<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

interface LectorComentariosInterface {

	/**
	 * Comentarios reales aprobados de `$postId` (sin pingbacks/trackbacks).
	 *
	 * @return list<ComentarioWordPress>
	 */
	public function obtenerAprobadosDe( int $postId ): array;
}
