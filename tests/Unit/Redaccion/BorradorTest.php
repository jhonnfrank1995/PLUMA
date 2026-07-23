<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Redaccion\Borrador;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 5.6: "máximo 2 ciclos; si al tercero no pasa, la pieza se marca
 * RETENIDA".
 *
 * @covers \Pluma\Redaccion\Borrador
 */
final class BorradorTest extends CasoDePruebaUnitario {

	private function borrador( int $numeroCiclo, bool $aprobado ): Borrador {
		return new Borrador( 1, 1, $numeroCiclo, 'contenido', array(), $aprobado, new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ) );
	}

	public function test_un_borrador_aprobado_nunca_agota_los_ciclos(): void {
		self::assertFalse( $this->borrador( 2, true )->agotoLosCiclos( 2 ) );
	}

	public function test_un_borrador_no_aprobado_por_debajo_del_maximo_no_agota_los_ciclos(): void {
		self::assertFalse( $this->borrador( 1, false )->agotoLosCiclos( 2 ) );
	}

	public function test_un_borrador_no_aprobado_en_el_maximo_de_ciclos_si_los_agota(): void {
		self::assertTrue( $this->borrador( 2, false )->agotoLosCiclos( 2 ) );
	}

	public function test_un_borrador_no_aprobado_por_encima_del_maximo_tambien_los_agota(): void {
		self::assertTrue( $this->borrador( 3, false )->agotoLosCiclos( 2 ) );
	}
}
