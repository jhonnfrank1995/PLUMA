<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Pluma\Redaccion\Diales;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 5.3: "Longitud preferida... Piezas de 600 palabras... Ensayos
 * de 1.800" — los extremos documentados de la interpolación.
 *
 * @covers \Pluma\Redaccion\Diales
 */
final class DialesTest extends CasoDePruebaUnitario {

	private function conLongitud( int $longitudPreferida ): Diales {
		return new Diales( 50, 50, 50, 50, 50, 50, 50, $longitudPreferida );
	}

	public function test_dial_en_cero_mapea_a_600_palabras(): void {
		self::assertSame( 600, $this->conLongitud( 0 )->longitudPalabrasObjetivo() );
	}

	public function test_dial_en_cien_mapea_a_1800_palabras(): void {
		self::assertSame( 1800, $this->conLongitud( 100 )->longitudPalabrasObjetivo() );
	}

	public function test_dial_en_cincuenta_mapea_al_punto_medio(): void {
		self::assertSame( 1200, $this->conLongitud( 50 )->longitudPalabrasObjetivo() );
	}

	public function test_ida_y_vuelta_por_array_preserva_los_valores(): void {
		$diales = new Diales( 80, 55, 40, 60, 65, 50, 70, 35 );

		self::assertEquals( $diales, Diales::desdeArray( $diales->aArray() ) );
	}
}
