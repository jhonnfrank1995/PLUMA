<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use DateTimeImmutable;

/**
 * Implementación real de RelojInterface: el reloj del sistema operativo.
 */
final class RelojSistema implements RelojInterface {

	public function ahora(): DateTimeImmutable {
		return new DateTimeImmutable();
	}
}
