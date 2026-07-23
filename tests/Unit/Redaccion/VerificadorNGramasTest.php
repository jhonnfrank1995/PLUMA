<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Redaccion\PuntoCorrector;
use Pluma\Redaccion\VerificadorNGramas;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Punto 3 del Corrector Interno (Libro Cap. 5.6): solapamiento n-grama.
 *
 * @covers \Pluma\Redaccion\VerificadorNGramas
 */
final class VerificadorNGramasTest extends CasoDePruebaUnitario {

	private function expediente( string $extracto ): Expediente {
		return new Expediente(
			'una tendencia',
			array( new HechoFuente( $extracto, 'https://example.com/fuente', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ) )
		);
	}

	public function test_aprueba_un_cuerpo_reescrito_con_sus_propias_palabras(): void {
		$expediente = $this->expediente( 'El banco central subió la tasa de interés al nueve por ciento este martes por la mañana' );
		$cuerpo     = 'La autoridad monetaria decidió endurecer su política este mes, en una decisión que sorprendió a los mercados.';

		$anotacion = ( new VerificadorNGramas() )->verificar( $expediente, $cuerpo );

		self::assertTrue( $anotacion->aprobado );
		self::assertSame( PuntoCorrector::SolapamientoNGrama, $anotacion->punto );
	}

	public function test_reprueba_una_copia_textual_de_ocho_palabras_o_mas(): void {
		$extracto   = 'El banco central subió la tasa de interés al nueve por ciento este martes por la mañana';
		$expediente = $this->expediente( $extracto );
		// Copia literal de una secuencia larga del extracto, solo cambia el remate.
		$cuerpo = 'Según fuentes oficiales, el banco central subió la tasa de interés al nueve por ciento este martes por la mañana, algo inédito.';

		$anotacion = ( new VerificadorNGramas() )->verificar( $expediente, $cuerpo );

		self::assertFalse( $anotacion->aprobado );
	}

	public function test_aprueba_un_cuerpo_demasiado_corto_para_evaluar(): void {
		$expediente = $this->expediente( 'un hecho cualquiera' );

		$anotacion = ( new VerificadorNGramas() )->verificar( $expediente, 'muy corto' );

		self::assertTrue( $anotacion->aprobado );
	}
}
