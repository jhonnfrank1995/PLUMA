<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Caso base para tests Unit: lógica pura con WordPress simulado vía Brain\Monkey.
 *
 * GOVERNANCE §4.4: ningún test Unit llama una API real; toda función de WP se
 * simula explícitamente con `Monkey\Functions\when()`/`expect()` en cada test.
 */
abstract class CasoDePruebaUnitario extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
