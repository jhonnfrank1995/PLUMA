<?php

declare(strict_types=1);

namespace Pluma\Kernel;

/**
 * Implementación real de AzarInterface: el generador de números del sistema.
 */
final class AzarSistema implements AzarInterface {

	public function entero( int $min, int $max ): int {
		return random_int( $min, $max );
	}
}
