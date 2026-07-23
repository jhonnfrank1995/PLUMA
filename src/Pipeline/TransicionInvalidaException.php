<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use RuntimeException;

final class TransicionInvalidaException extends RuntimeException {

	public function __construct( EstadoPieza $de, EstadoPieza $hacia ) {
		parent::__construct( "Transición inválida: {$de->value} → {$hacia->value} no existe en el grafo de estados." );
	}
}
