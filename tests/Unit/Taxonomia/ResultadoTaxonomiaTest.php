<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use Brain\Monkey\Functions;
use Pluma\Taxonomia\EtiquetaAsignada;
use Pluma\Taxonomia\ResultadoTaxonomia;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * `RepositorioPiezas::actualizarResultadoTaxonomia()` persiste
 * `ResultadoTaxonomia` completo como JSON — si el ida-y-vuelta pierde un
 * campo, la Sala de Revisión mostraría una clasificación incompleta.
 *
 * @covers \Pluma\Taxonomia\ResultadoTaxonomia
 * @covers \Pluma\Taxonomia\EtiquetaAsignada
 */
final class ResultadoTaxonomiaTest extends CasoDePruebaUnitario {

	private function resultado(): ResultadoTaxonomia {
		return new ResultadoTaxonomia(
			'Economía',
			array(
				new EtiquetaAsignada( 1, 'Banco de la República', false, false ),
				new EtiquetaAsignada( 2, 'Reforma pensional', true, true ),
			)
		);
	}

	public function test_ida_y_vuelta_por_array_preserva_categoria_y_etiquetas(): void {
		$original     = $this->resultado();
		$reconstruida = ResultadoTaxonomia::desdeArray( $original->aArray() );

		self::assertSame( 'Economía', $reconstruida->categoriaAsignada );
		self::assertCount( 2, $reconstruida->etiquetas );
		self::assertSame( 'Banco de la República', $reconstruida->etiquetas[0]->nombre );
		self::assertFalse( $reconstruida->etiquetas[0]->esNueva );
		self::assertTrue( $reconstruida->etiquetas[1]->enCuarentena );
	}

	public function test_ida_y_vuelta_preserva_una_categoria_nula(): void {
		$original     = new ResultadoTaxonomia( null, array() );
		$reconstruida = ResultadoTaxonomia::desdeArray( $original->aArray() );

		self::assertNull( $reconstruida->categoriaAsignada );
		self::assertSame( array(), $reconstruida->etiquetas );
	}

	public function test_el_json_encode_de_aArray_produce_un_string_valido(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$json = wp_json_encode( $this->resultado()->aArray() );

		self::assertIsString( $json );
		self::assertSame( $this->resultado()->aArray(), json_decode( $json, true ) );
	}
}
