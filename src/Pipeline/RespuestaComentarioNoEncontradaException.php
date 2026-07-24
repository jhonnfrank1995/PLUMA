<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use RuntimeException;

final class RespuestaComentarioNoEncontradaException extends RuntimeException {

	public function __construct( int $respuestaId ) {
		parent::__construct( "Respuesta de comentario {$respuestaId} no encontrada." );
	}
}
