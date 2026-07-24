<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Pluma\Redaccion\VerificadorComentarioSustantivo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Redaccion\VerificadorComentarioSustantivo
 */
final class VerificadorComentarioSustantivoTest extends CasoDePruebaUnitario {

	public function test_un_comentario_corto_no_es_sustantivo(): void {
		self::assertFalse( ( new VerificadorComentarioSustantivo() )->esSustantivo( 'primero!' ) );
	}

	public function test_un_comentario_solo_de_espacios_no_es_sustantivo(): void {
		self::assertFalse( ( new VerificadorComentarioSustantivo() )->esSustantivo( '                                          ' ) );
	}

	public function test_un_comentario_largo_y_con_contenido_es_sustantivo(): void {
		$comentario = 'No estoy de acuerdo con el análisis, creo que faltó contexto sobre el impacto real en los precios.';

		self::assertTrue( ( new VerificadorComentarioSustantivo() )->esSustantivo( $comentario ) );
	}
}
