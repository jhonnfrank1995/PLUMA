<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Brain\Monkey\Functions;
use Pluma\Redaccion\AvisoTransparenciaIa;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * GOVERNANCE §2.6: "el bloque configurable existe siempre; la opción
 * controla el formato, no la existencia".
 *
 * @covers \Pluma\Redaccion\AvisoTransparenciaIa
 */
final class AvisoTransparenciaIaTest extends CasoDePruebaUnitario {

	private function mockearFunciones(): void {
		Functions\when( 'esc_html' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );
		Functions\when( '__' )->alias( static fn ( string $s ): string => $s );
	}

	public function test_por_defecto_usa_el_formato_breve(): void {
		$this->mockearFunciones();
		Functions\when( 'get_option' )->justReturn( false );

		$html = ( new AvisoTransparenciaIa() )->comoHtml( 'Valentina Ruiz' );

		self::assertStringContainsString( 'Valentina Ruiz', $html );
		self::assertStringContainsString( 'periodista sintético', $html );
	}

	public function test_el_formato_extendido_produce_un_texto_distinto_pero_siempre_presente(): void {
		$this->mockearFunciones();
		Functions\when( 'get_option' )->justReturn( 'extendido' );

		$html = ( new AvisoTransparenciaIa() )->comoHtml( 'Valentina Ruiz' );

		self::assertStringContainsString( 'Valentina Ruiz', $html );
		self::assertStringContainsString( 'dirección editorial humana', $html );
	}

	public function test_un_valor_de_opcion_desconocido_cae_al_formato_breve_sin_desaparecer(): void {
		$this->mockearFunciones();
		Functions\when( 'get_option' )->justReturn( 'formato-que-no-existe' );

		$html = ( new AvisoTransparenciaIa() )->comoHtml( 'Valentina Ruiz' );

		self::assertNotSame( '', trim( $html ) );
		self::assertStringContainsString( 'Valentina Ruiz', $html );
	}

	public function test_el_aviso_escapa_el_nombre_del_periodista(): void {
		$this->mockearFunciones();
		Functions\when( 'get_option' )->justReturn( false );

		$html = ( new AvisoTransparenciaIa() )->comoHtml( '<script>alert(1)</script>' );

		self::assertStringNotContainsString( '<script>', $html );
	}
}
