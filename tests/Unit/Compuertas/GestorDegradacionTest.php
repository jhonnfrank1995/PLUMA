<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Pluma\Compuertas\DiagnosticoRiesgo;
use Pluma\Compuertas\GestorDegradacion;
use Pluma\Compuertas\ModoOperacion;
use Pluma\Compuertas\TemaRegulado;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 2.4/8.2: "esta degradación automática por sensibilidad no es
 * opcional en el diseño: es el seguro de vida legal y reputacional del
 * producto."
 *
 * @covers \Pluma\Compuertas\GestorDegradacion
 */
final class GestorDegradacionTest extends CasoDePruebaUnitario {

	private function riesgo(
		bool $tragedia = false,
		bool $menores = false,
		bool $salud = false,
		bool $violencia = false,
		?TemaRegulado $temaRegulado = null
	): DiagnosticoRiesgo {
		return new DiagnosticoRiesgo( $tragedia, $menores, $salud, $violencia, false, '', false, $temaRegulado );
	}

	public function test_sin_sensibilidad_el_modo_global_no_cambia(): void {
		$gestor = new GestorDegradacion();

		self::assertSame( ModoOperacion::Autonomo, $gestor->modoEfectivo( ModoOperacion::Autonomo, $this->riesgo() ) );
		self::assertSame( ModoOperacion::Copiloto, $gestor->modoEfectivo( ModoOperacion::Copiloto, $this->riesgo() ) );
		self::assertSame( ModoOperacion::Piloto, $gestor->modoEfectivo( ModoOperacion::Piloto, $this->riesgo() ) );
	}

	public function test_tragedia_degrada_autonomo_a_copiloto(): void {
		$gestor = new GestorDegradacion();

		self::assertSame( ModoOperacion::Copiloto, $gestor->modoEfectivo( ModoOperacion::Autonomo, $this->riesgo( tragedia: true ) ) );
	}

	public function test_sensibilidad_no_degrada_mas_alla_de_copiloto_o_piloto(): void {
		$gestor = new GestorDegradacion();

		self::assertSame( ModoOperacion::Copiloto, $gestor->modoEfectivo( ModoOperacion::Copiloto, $this->riesgo( salud: true ) ) );
		self::assertSame( ModoOperacion::Piloto, $gestor->modoEfectivo( ModoOperacion::Piloto, $this->riesgo( violencia: true ) ) );
	}

	public function test_menores_fuerza_piloto_completo_sin_importar_el_modo_global(): void {
		$gestor = new GestorDegradacion();

		self::assertSame( ModoOperacion::Piloto, $gestor->modoEfectivo( ModoOperacion::Autonomo, $this->riesgo( menores: true ) ) );
		self::assertSame( ModoOperacion::Piloto, $gestor->modoEfectivo( ModoOperacion::Copiloto, $this->riesgo( menores: true ) ) );
	}
}
