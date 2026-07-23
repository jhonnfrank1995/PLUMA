<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Brain\Monkey\Functions;
use Pluma\Redaccion\BloqueEditor;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Redaccion\BloqueEditor
 */
final class BloqueEditorTest extends CasoDePruebaUnitario {

	public function test_como_html_escapa_el_comentario_y_la_pregunta(): void {
		Functions\when( 'esc_html' )->alias( static fn ( string $s ): string => htmlspecialchars( $s, ENT_QUOTES ) );

		$bloque = new BloqueEditor( 'Comentario con <script>', '¿Y tú qué harías?' );

		$html = $bloque->comoHtml();

		self::assertStringContainsString( 'Comentario con &lt;script&gt;', $html );
		self::assertStringContainsString( '¿Y tú qué harías?', $html );
		self::assertStringNotContainsString( '<script>', $html );
	}
}
