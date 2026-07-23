<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Pluma\Compuertas\CompuertaException;
use Pluma\Compuertas\VerificadorTruncamiento;
use Pluma\Proveedores\RespuestaLenguaje;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Compuertas\VerificadorTruncamiento
 */
final class VerificadorTruncamientoTest extends CasoDePruebaUnitario {

	public function test_no_lanza_excepcion_si_la_respuesta_no_esta_truncada(): void {
		$respuesta = new RespuestaLenguaje( 'contenido', 10, 10, 0.0, 'falso', 'modelo', 1, false );

		VerificadorTruncamiento::asegurar( $respuesta );

		$this->expectNotToPerformAssertions();
	}

	public function test_lanza_excepcion_si_la_respuesta_esta_truncada(): void {
		$respuesta = new RespuestaLenguaje( 'contenido', 10, 10, 0.0, 'falso', 'modelo', 1, true );

		$this->expectException( CompuertaException::class );

		VerificadorTruncamiento::asegurar( $respuesta );
	}
}
