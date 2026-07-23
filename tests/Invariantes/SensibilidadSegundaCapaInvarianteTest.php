<?php

declare(strict_types=1);

namespace Pluma\Tests\Invariantes;

use Pluma\Compuertas\DiagnosticoRiesgo;
use Pluma\Compuertas\GestorDegradacion;
use Pluma\Compuertas\ModoOperacion;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * GOVERNANCE §2.2 — "La degradación por sensibilidad (tragedia/menores/salud
 * → nunca Autónomo, sátira bloqueada) se implementa en Pluma\Compuertas Y
 * NINGUNA opción de usuario la anula." Defensa en profundidad de dos capas
 * independientes (pl-compuertas §3): la primera vive en
 * `Pluma\Redaccion\MatrizTonos` (cubierta en
 * `tests/Invariantes/SatiraBloqueadaInvarianteTest`, Etapa 2); esta es la
 * segunda, en `Pluma\Compuertas`.
 *
 * Si este test se pone en rojo, un modo Autónomo mal configurado podría
 * publicar sin supervisión humana una pieza sobre una tragedia, menores o
 * salud — exactamente el escenario que la degradación existe para impedir.
 *
 * @covers \Pluma\Compuertas\GestorDegradacion
 */
final class SensibilidadSegundaCapaInvarianteTest extends CasoDePruebaUnitario {

	/**
	 * @return iterable<string, array{0: DiagnosticoRiesgo}>
	 */
	public static function senalesDeSensibilidad(): iterable {
		yield 'tragedia' => array( new DiagnosticoRiesgo( true, false, false, false, false, '', false, null ) );
		yield 'salud'    => array( new DiagnosticoRiesgo( false, false, true, false, false, '', false, null ) );
		yield 'violencia' => array( new DiagnosticoRiesgo( false, false, false, true, false, '', false, null ) );
	}

	/**
	 * @dataProvider senalesDeSensibilidad
	 */
	public function test_ninguna_senal_de_sensibilidad_deja_el_modo_autonomo_intacto( DiagnosticoRiesgo $riesgo ): void {
		$modoEfectivo = ( new GestorDegradacion() )->modoEfectivo( ModoOperacion::Autonomo, $riesgo );

		self::assertNotSame(
			ModoOperacion::Autonomo,
			$modoEfectivo,
			'Ninguna señal de sensibilidad (tragedia/salud/violencia) puede dejar una pieza en modo Autónomo sin supervisión humana.'
		);
	}

	public function test_menores_es_la_senal_mas_severa_y_fuerza_piloto_incluso_desde_copiloto(): void {
		$riesgoMenores = new DiagnosticoRiesgo( false, true, false, false, false, '', false, null );

		self::assertSame( ModoOperacion::Piloto, ( new GestorDegradacion() )->modoEfectivo( ModoOperacion::Autonomo, $riesgoMenores ) );
		self::assertSame( ModoOperacion::Piloto, ( new GestorDegradacion() )->modoEfectivo( ModoOperacion::Copiloto, $riesgoMenores ) );
	}

	public function test_sin_ninguna_senal_de_sensibilidad_el_modo_global_se_respeta(): void {
		$sinSenales = new DiagnosticoRiesgo( false, false, false, false, false, '', false, null );

		self::assertSame( ModoOperacion::Autonomo, ( new GestorDegradacion() )->modoEfectivo( ModoOperacion::Autonomo, $sinSenales ) );
	}
}
