<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Compuertas\CompuertaCalidad;
use Pluma\Compuertas\VerificadorLegibilidad;
use Pluma\Redaccion\AnotacionCorrector;
use Pluma\Redaccion\Borrador;
use Pluma\Redaccion\EsqueletoPieza;
use Pluma\Redaccion\PuntoCorrector;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 8.1: "Umbral configurable; por debajo, RETENIDA con diagnóstico."
 *
 * @covers \Pluma\Compuertas\CompuertaCalidad
 */
final class CompuertaCalidadTest extends CasoDePruebaUnitario {

	private const TEXTO_LEGIBLE = 'El banco central subió la tasa de interés al nueve por ciento este martes. '
		. 'Los analistas esperaban un movimiento más cauto según el último informe trimestral publicado.';

	private function esqueletoCompleto(): EsqueletoPieza {
		return new EsqueletoPieza( 'gancho', 'hechos esenciales', array( 'movimiento 1', 'movimiento 2' ), 'contraargumento', 'remate' );
	}

	private function borrador( bool $hechos, bool $proporcion, bool $voz ): Borrador {
		return new Borrador(
			1,
			1,
			1,
			'contenido',
			array(
				new AnotacionCorrector( PuntoCorrector::Hechos, $hechos, $hechos ? 'ok' : 'sin respaldo' ),
				new AnotacionCorrector( PuntoCorrector::ProporcionInterpretativa, $proporcion, $proporcion ? 'ok' : 'demasiado narrativo' ),
				new AnotacionCorrector( PuntoCorrector::Voz, $voz, $voz ? 'ok' : 'sin rasgos de voz' ),
			),
			$hechos && $proporcion && $voz,
			new DateTimeImmutable( '2026-07-22T12:00:00+00:00' )
		);
	}

	public function test_aprueba_un_borrador_completo_y_bien_sustentado(): void {
		Functions\when( 'get_option' )->justReturn( 70 );

		$diagnostico = ( new CompuertaCalidad( new VerificadorLegibilidad() ) )->evaluar(
			$this->borrador( true, true, true ),
			$this->esqueletoCompleto(),
			self::TEXTO_LEGIBLE,
			true
		);

		self::assertTrue( $diagnostico->aprobada() );
		self::assertSame( 100, $diagnostico->puntuacionTotal );
	}

	public function test_reprueba_si_falta_sustento_en_el_expediente(): void {
		Functions\when( 'get_option' )->justReturn( 70 );

		$diagnostico = ( new CompuertaCalidad( new VerificadorLegibilidad() ) )->evaluar(
			$this->borrador( false, true, true ),
			$this->esqueletoCompleto(),
			self::TEXTO_LEGIBLE,
			true
		);

		self::assertFalse( $diagnostico->aprobada() );
		self::assertSame( 75, $diagnostico->puntuacionTotal );
		self::assertStringContainsString( 'Sustento', implode( ' ', $diagnostico->detalle ) );
	}

	public function test_reprueba_si_la_estructura_esta_incompleta_y_el_umbral_no_lo_perdona(): void {
		Functions\when( 'get_option' )->justReturn( 85 );

		$esqueletoIncompleto = new EsqueletoPieza( '', 'hechos', array( 'm1', 'm2' ), 'contra', 'remate' );

		$diagnostico = ( new CompuertaCalidad( new VerificadorLegibilidad() ) )->evaluar(
			$this->borrador( true, true, true ),
			$esqueletoIncompleto,
			self::TEXTO_LEGIBLE,
			true
		);

		self::assertSame( 80, $diagnostico->puntuacionTotal );
		self::assertFalse( $diagnostico->aprobada() );
		self::assertStringContainsString( 'Estructura incompleta', implode( ' ', $diagnostico->detalle ) );
	}

	public function test_faltar_el_bloque_del_editor_cuenta_como_estructura_incompleta(): void {
		Functions\when( 'get_option' )->justReturn( 85 );

		$diagnostico = ( new CompuertaCalidad( new VerificadorLegibilidad() ) )->evaluar(
			$this->borrador( true, true, true ),
			$this->esqueletoCompleto(),
			self::TEXTO_LEGIBLE,
			false
		);

		self::assertSame( 80, $diagnostico->puntuacionTotal );
		self::assertFalse( $diagnostico->aprobada() );
	}

	public function test_el_umbral_se_acota_a_cien(): void {
		Functions\when( 'get_option' )->justReturn( 101 );

		$diagnostico = ( new CompuertaCalidad( new VerificadorLegibilidad() ) )->evaluar(
			$this->borrador( true, true, true ),
			$this->esqueletoCompleto(),
			self::TEXTO_LEGIBLE,
			true
		);

		self::assertSame( 100, $diagnostico->umbral );
		self::assertTrue( $diagnostico->aprobada() );
	}
}
