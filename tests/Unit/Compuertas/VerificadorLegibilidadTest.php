<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Pluma\Compuertas\VerificadorLegibilidad;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Compuertas\VerificadorLegibilidad
 */
final class VerificadorLegibilidadTest extends CasoDePruebaUnitario {

	public function test_puntua_maximo_un_texto_con_frases_de_longitud_comoda(): void {
		// Frases de ~15 palabras cada una, dentro del rango 12-22.
		$texto = 'El banco central subió la tasa de interés al nueve por ciento este martes. '
			. 'Los analistas esperaban un movimiento más cauto según el último informe trimestral publicado.';

		self::assertSame( 20, ( new VerificadorLegibilidad() )->puntuar( $texto ) );
	}

	public function test_puntua_cero_un_texto_vacio(): void {
		self::assertSame( 0, ( new VerificadorLegibilidad() )->puntuar( '' ) );
	}

	public function test_puntua_cero_frases_extremadamente_cortas(): void {
		self::assertSame( 0, ( new VerificadorLegibilidad() )->puntuar( 'Sí. No. Tal vez.' ) );
	}

	public function test_puntua_cero_frases_extremadamente_largas(): void {
		$palabras = array_fill( 0, 60, 'palabra' );
		$texto    = implode( ' ', $palabras ) . '.';

		self::assertSame( 0, ( new VerificadorLegibilidad() )->puntuar( $texto ) );
	}

	public function test_puntua_a_medias_frases_moderadamente_largas(): void {
		$palabras = array_fill( 0, 30, 'palabra' );
		$texto    = implode( ' ', $palabras ) . '.';

		$puntuacion = ( new VerificadorLegibilidad() )->puntuar( $texto );

		self::assertGreaterThan( 0, $puntuacion );
		self::assertLessThan( 20, $puntuacion );
	}
}
