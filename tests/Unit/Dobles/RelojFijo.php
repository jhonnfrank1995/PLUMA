<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Dobles;

use DateTimeImmutable;
use Pluma\Kernel\RelojInterface;

/**
 * Reloj inyectable de prueba: siempre devuelve el mismo instante (pl-testing:
 * "tiempo y azar inyectables — cero time() directo en src/").
 */
final class RelojFijo implements RelojInterface {

	private DateTimeImmutable $instante;

	public function __construct( string $fechaHora = '2026-07-22T12:00:00+00:00' ) {
		$this->instante = new DateTimeImmutable( $fechaHora );
	}

	public function ahora(): DateTimeImmutable {
		return $this->instante;
	}
}
