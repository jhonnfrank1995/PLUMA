<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use RuntimeException;

final class PiezaNoEncontradaException extends RuntimeException {

	public function __construct( int $piezaId ) {
		parent::__construct( "Pieza {$piezaId} no encontrada." );
	}
}
