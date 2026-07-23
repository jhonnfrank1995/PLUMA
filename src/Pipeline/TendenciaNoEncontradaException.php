<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use RuntimeException;

final class TendenciaNoEncontradaException extends RuntimeException {

	public function __construct( int $tendenciaId ) {
		parent::__construct( "Tendencia {$tendenciaId} no encontrada." );
	}
}
