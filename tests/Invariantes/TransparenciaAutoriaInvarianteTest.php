<?php

declare(strict_types=1);

namespace Pluma\Tests\Invariantes;

use Brain\Monkey\Functions;
use Pluma\Redaccion\AvisoTransparenciaIa;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * GOVERNANCE §2.6 — "Transparencia de autoría IA: el bloque configurable
 * existe siempre; la opción controla el formato, no la existencia."
 *
 * Si este test se pone en rojo, existe una configuración capaz de hacer
 * desaparecer el aviso de autoría IA — exactamente lo que la regla prohíbe.
 */
final class TransparenciaAutoriaInvarianteTest extends CasoDePruebaUnitario {

	private function mockearFunciones(): void {
		Functions\when( 'esc_html' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
		Functions\when( '__' )->alias( static fn ( string $s ): string => $s );
	}

	/**
	 * @return iterable<string, array{0: mixed}>
	 */
	public static function valoresDeOpcionPosibles(): iterable {
		yield 'opción no configurada (false de get_option)' => array( false );
		yield 'formato breve explícito' => array( 'breve' );
		yield 'formato extendido explícito' => array( 'extendido' );
		yield 'valor corrupto o de una versión futura' => array( 'formato-inventado-por-una-migracion-rota' );
		yield 'valor del tipo equivocado' => array( 123 );
		yield 'cadena vacía' => array( '' );
	}

	/**
	 * @dataProvider valoresDeOpcionPosibles
	 */
	public function test_el_aviso_nunca_desaparece_sin_importar_el_valor_de_la_opcion( mixed $valorOpcion ): void {
		$this->mockearFunciones();
		Functions\when( 'get_option' )->justReturn( $valorOpcion );

		$html = ( new AvisoTransparenciaIa() )->comoHtml( 'Periodista de Prueba' );

		self::assertNotSame( '', trim( $html ), 'El aviso de transparencia de autoría IA debe existir sin importar el valor de la opción.' );
		self::assertStringContainsString( 'Periodista de Prueba', $html );
	}
}
