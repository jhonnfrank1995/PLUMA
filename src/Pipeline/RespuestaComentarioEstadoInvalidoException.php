<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use RuntimeException;

/**
 * Se lanza al intentar aprobar/descartar una respuesta que ya no está
 * `pendiente_aprobacion` (candado optimista, mismo espíritu que
 * `Transicionador`: la segunda acción sobre el mismo borrador no debe
 * publicar dos veces el mismo comentario).
 */
final class RespuestaComentarioEstadoInvalidoException extends RuntimeException {

	public function __construct( int $respuestaId ) {
		parent::__construct( "La respuesta de comentario {$respuestaId} ya fue resuelta." );
	}
}
