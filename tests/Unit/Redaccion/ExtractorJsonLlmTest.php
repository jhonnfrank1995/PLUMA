<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Pluma\Redaccion\DecisionEditorialException;
use Pluma\Redaccion\ExtractorJsonLlm;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Redaccion\ExtractorJsonLlm
 */
final class ExtractorJsonLlmTest extends CasoDePruebaUnitario {

	public function test_extrae_un_json_limpio(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( '{"a": 1}' ) );
	}

	public function test_extrae_un_json_envuelto_en_cerca_de_markdown_con_etiqueta(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( "```json\n{\"a\": 1}\n```" ) );
	}

	public function test_extrae_un_json_envuelto_en_cerca_de_markdown_sin_etiqueta(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( "```\n{\"a\": 1}\n```" ) );
	}

	public function test_extrae_un_json_con_texto_alrededor(): void {
		self::assertSame( array( 'a' => 1 ), ExtractorJsonLlm::extraer( "Aquí tienes la respuesta:\n{\"a\": 1}\nEspero que ayude." ) );
	}

	public function test_lanza_excepcion_si_no_hay_llaves(): void {
		$this->expectException( DecisionEditorialException::class );

		ExtractorJsonLlm::extraer( 'no hay json aquí' );
	}

	public function test_lanza_excepcion_si_el_json_esta_corrupto(): void {
		$this->expectException( DecisionEditorialException::class );

		ExtractorJsonLlm::extraer( '{"a": }' );
	}
}
