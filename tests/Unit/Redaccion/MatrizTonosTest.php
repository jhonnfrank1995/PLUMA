<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\MatrizTonosIncompletaException;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 5.3: el bloqueo de sátira en Tragedia "no es un valor de la
 * matriz: es una regla de sistema" — se impone sobre cualquier configuración.
 *
 * @covers \Pluma\Redaccion\MatrizTonos
 */
final class MatrizTonosTest extends CasoDePruebaUnitario {

	public function test_la_fila_de_tragedia_se_impone_aunque_no_se_configure(): void {
		$matriz = MatrizTonos::desdeFilas(
			array(
				new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ),
			)
		);

		$filaTragedia = $matriz->paraTipo( TipoNoticia::Tragedia );

		self::assertSame( Tono::InformativoEmpatico, $filaTragedia->tonoDominante );
		self::assertSame( Tono::Analitico, $filaTragedia->tonoApoyo );
		self::assertSame( NivelSatiraPermitida::Bloqueada, $filaTragedia->nivelSatira );
	}

	public function test_una_fila_de_tragedia_configurada_maliciosamente_es_ignorada_y_reemplazada(): void {
		// Un periodista con sátira 100 no puede, por ninguna vía, terminar con
		// una fila de Tragedia que permita sátira — ni siquiera si alguien
		// construyó la matriz con esa fila explícita.
		$matriz = MatrizTonos::desdeFilas(
			array(
				new EntradaMatrizTono( TipoNoticia::Tragedia, Tono::Humoristico, Tono::Opinion, NivelSatiraPermitida::PiezaCompleta ),
			)
		);

		self::assertSame( NivelSatiraPermitida::Bloqueada, $matriz->paraTipo( TipoNoticia::Tragedia )->nivelSatira );
	}

	public function test_consultar_un_tipo_sin_fila_configurada_lanza_excepcion(): void {
		$matriz = MatrizTonos::desdeFilas( array() );

		$this->expectException( MatrizTonosIncompletaException::class );

		$matriz->paraTipo( TipoNoticia::CulturaViral );
	}

	public function test_ida_y_vuelta_por_array_preserva_las_filas_configuradas(): void {
		$original = MatrizTonos::desdeFilas(
			array(
				new EntradaMatrizTono( TipoNoticia::AnuncioCorporativo, Tono::Analitico, Tono::Critico, NivelSatiraPermitida::EnRemate ),
				new EntradaMatrizTono( TipoNoticia::CulturaViral, Tono::Humoristico, Tono::Opinion, NivelSatiraPermitida::PiezaCompleta ),
			)
		);

		$reconstruida = MatrizTonos::desdeArray( $original->aArray() );

		self::assertEquals( $original->paraTipo( TipoNoticia::AnuncioCorporativo ), $reconstruida->paraTipo( TipoNoticia::AnuncioCorporativo ) );
		self::assertEquals( $original->paraTipo( TipoNoticia::CulturaViral ), $reconstruida->paraTipo( TipoNoticia::CulturaViral ) );
		self::assertSame( NivelSatiraPermitida::Bloqueada, $reconstruida->paraTipo( TipoNoticia::Tragedia )->nivelSatira );
	}
}
