<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Sensores;

use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Sensores\PuntuacionOportunidad
 */
final class PuntuacionOportunidadTest extends CasoDePruebaUnitario {

	public function test_velocidad_y_afinidad_maximas_dan_total_100(): void {
		$puntuacion = PuntuacionOportunidad::calcular( 100.0, 100.0 );

		self::assertSame( 100.0, $puntuacion->velocidad );
		self::assertSame( 100.0, $puntuacion->afinidad );
		self::assertSame( 100.0, $puntuacion->total );
	}

	public function test_velocidad_y_afinidad_en_cero_dan_total_0(): void {
		$puntuacion = PuntuacionOportunidad::calcular( 0.0, 0.0 );

		self::assertSame( 0.0, $puntuacion->total );
	}

	public function test_normaliza_sobre_el_65_por_ciento_disponible(): void {
		// Solo velocidad al máximo (peso 35 de 65 disponibles) = 53.85%.
		$puntuacion = PuntuacionOportunidad::calcular( 100.0, 0.0 );

		self::assertEqualsWithDelta( 53.85, $puntuacion->total, 0.01 );
	}

	public function test_acota_valores_fuera_de_rango_0_100(): void {
		$puntuacion = PuntuacionOportunidad::calcular( 150.0, -20.0 );

		self::assertSame( 100.0, $puntuacion->velocidad );
		self::assertSame( 0.0, $puntuacion->afinidad );
	}
}
