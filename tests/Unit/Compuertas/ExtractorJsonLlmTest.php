<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Pluma\Compuertas\CompuertaException;
use Pluma\Compuertas\ExtractorJsonLlm;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Compuertas\ExtractorJsonLlm
 */
final class ExtractorJsonLlmTest extends CasoDePruebaUnitario {

	public function test_extrae_un_json_limpio(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( '{"a": 1}' ) );
	}

	public function test_extrae_un_json_envuelto_en_cerca_de_markdown(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( "```json\n{\"a\": 1}\n```" ) );
	}

	public function test_extrae_un_json_con_texto_alrededor(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( "Aquí tienes:\n{\"a\": 1}\nSaludos." ) );
	}

	public function test_lanza_excepcion_si_no_hay_llaves(): void {
		$this->expectException( CompuertaException::class );

		ExtractorJsonLlm::extraer( 'no hay json aquí' );
	}

	public function test_lanza_excepcion_si_el_json_esta_corrupto(): void {
		$this->expectException( CompuertaException::class );

		ExtractorJsonLlm::extraer( '{"a": }' );
	}
}
