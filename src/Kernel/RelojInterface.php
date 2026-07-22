<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use DateTimeImmutable;

/**
 * Contrato de tiempo inyectable (pl-testing: "cero time() directo en src/").
 *
 * Cualquier clase que necesite "el momento actual" recibe esta interfaz por
 * constructor en vez de llamar `time()`/`new DateTimeImmutable()` de forma
 * directa, para que los tests controlen el reloj con un valor fijo.
 */
interface RelojInterface {

	public function ahora(): DateTimeImmutable;
}
