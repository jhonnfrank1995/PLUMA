<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\PlantillaPeriodista;
use Pluma\Redaccion\PlantillasSiembra;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 5.8: banco inicial recomendado de 3 periodistas complementarios.
 *
 * @covers \Pluma\Redaccion\PlantillasSiembra
 */
final class PlantillasSiembraTest extends CasoDePruebaUnitario {

	public function test_todas_devuelve_las_tres_plantillas_recomendadas_por_el_libro(): void {
		$plantillas = PlantillasSiembra::todas();

		self::assertCount( 3, $plantillas );

		foreach ( $plantillas as $plantilla ) {
			self::assertInstanceOf( PlantillaPeriodista::class, $plantilla );
			self::assertSame( EstadoPeriodista::Activo, $plantilla->estado );
			// Ninguna plantilla puede colar una fila de Tragedia que permita sátira:
			// la regla de sistema se verifica también en la materia prima de siembra.
			self::assertSame(
				NivelSatiraPermitida::Bloqueada,
				$plantilla->matrizTonos->paraTipo( TipoNoticia::Tragedia )->nivelSatira
			);
		}
	}

	public function test_las_tres_plantillas_tienen_nombres_y_diales_distintos(): void {
		$analista   = PlantillasSiembra::analistaDeDatosSobrio();
		$columnista = PlantillasSiembra::columnistaCriticaVehemente();
		$cronista   = PlantillasSiembra::cronistaSatirico();

		$nombres = array( $analista->nombre, $columnista->nombre, $cronista->nombre );
		self::assertSame( $nombres, array_unique( $nombres ) );

		self::assertGreaterThan( $columnista->diales->satira, $cronista->diales->satira );
		self::assertGreaterThan( $cronista->diales->densidadDatos, $analista->diales->densidadDatos );
	}

	public function test_la_columnista_replica_los_diales_de_valentina_del_libro(): void {
		$columnista = PlantillasSiembra::columnistaCriticaVehemente();

		self::assertSame( 80, $columnista->diales->agudezaCritica );
		self::assertSame( 55, $columnista->diales->humor );
		self::assertSame( 40, $columnista->diales->satira );
	}
}
