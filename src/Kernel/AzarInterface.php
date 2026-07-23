<?php

declare(strict_types=1);

namespace Pluma\Kernel;

/**
 * Contrato de azar inyectable (GOVERNANCE §4.3, pl-testing): jitter y
 * desempates del orquestador pasan SIEMPRE por aquí — cero `random_*`
 * directo en `src/`, para que los tests inyecten una semilla fija.
 */
interface AzarInterface {

	/**
	 * Entero pseudoaleatorio en `[$min, $max]`, ambos inclusive.
	 */
	public function entero( int $min, int $max ): int;
}
