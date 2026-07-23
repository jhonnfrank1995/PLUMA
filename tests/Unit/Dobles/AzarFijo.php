<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Dobles;

use Pluma\Kernel\AzarInterface;

/**
 * Azar inyectable de prueba: siempre devuelve el mismo entero (pl-testing:
 * "tiempo y azar inyectables — cero random_* directo en src/").
 */
final class AzarFijo implements AzarInterface {

	public function __construct( private readonly int $valorFijo = 0 ) {
	}

	public function entero( int $min, int $max ): int {
		return $this->valorFijo;
	}
}
