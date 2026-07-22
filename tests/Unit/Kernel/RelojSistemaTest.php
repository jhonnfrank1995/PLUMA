<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use DateTimeImmutable;
use Pluma\Kernel\RelojSistema;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Kernel\RelojSistema
 */
final class RelojSistemaTest extends CasoDePruebaUnitario {

	public function test_ahora_devuelve_un_instante_cercano_al_reloj_real(): void {
		$antes   = new DateTimeImmutable();
		$ahora   = ( new RelojSistema() )->ahora();
		$despues = new DateTimeImmutable();

		self::assertGreaterThanOrEqual( $antes->getTimestamp(), $ahora->getTimestamp() );
		self::assertLessThanOrEqual( $despues->getTimestamp(), $ahora->getTimestamp() );
	}
}
