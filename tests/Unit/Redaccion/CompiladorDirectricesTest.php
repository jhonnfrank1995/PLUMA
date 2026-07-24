<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Redaccion\CompiladorDirectrices;
use Pluma\Redaccion\ConductaVersion;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\Periodista;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use Pluma\Redaccion\VocabularioProhibidoGlobal;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Redaccion\CompiladorDirectrices
 */
final class CompiladorDirectricesTest extends CasoDePruebaUnitario {

	private function periodista( int $satira ): Periodista {
		$diales   = new Diales( 80, 55, $satira, 55, 75, 60, 60, 65 );
		$reglas   = new ReglasConducta(
			'Escéptica del poder.',
			array( 'menores de edad' ),
			array( 'abre con una pregunta retórica', 'cierra con una cifra' ),
			array( 'muletilla propia prohibida' ),
			TratamientoLector::Tu,
			'¿A quién le crees aquí?'
		);
		$matriz   = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
		);
		$conducta = new ConductaVersion( 1, 1, $diales, $reglas, $matriz, false, new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ) );

		return new Periodista(
			1,
			'Valentina Ruiz',
			null,
			'Economista de formación.',
			RolPeriodista::Columnista,
			array(),
			EstadoPeriodista::Activo,
			$conducta,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);
	}

	public function test_la_satira_bloqueada_por_sistema_se_impone_aunque_el_dial_sea_alto(): void {
		$directrices = ( new CompiladorDirectrices() )->compilar(
			$this->periodista( 90 ),
			Tono::InformativoEmpatico,
			Tono::Analitico,
			NivelSatiraPermitida::Bloqueada
		);

		self::assertStringContainsString( 'SÁTIRA BLOQUEADA POR SISTEMA', $directrices );
	}

	public function test_un_nivel_de_satira_permitido_describe_el_permiso_concreto(): void {
		$directrices = ( new CompiladorDirectrices() )->compilar(
			$this->periodista( 40 ),
			Tono::Humoristico,
			Tono::Opinion,
			NivelSatiraPermitida::PiezaCompleta
		);

		self::assertStringContainsString( 'puedes construir la pieza entera con tono satírico', $directrices );
		self::assertStringNotContainsString( 'BLOQUEADA POR SISTEMA', $directrices );
	}

	public function test_incluye_el_vocabulario_prohibido_propio_y_el_global(): void {
		$directrices = ( new CompiladorDirectrices() )->compilar(
			$this->periodista( 40 ),
			Tono::Analitico,
			Tono::Critico,
			NivelSatiraPermitida::No
		);

		self::assertStringContainsString( 'muletilla propia prohibida', $directrices );
		self::assertStringContainsString( VocabularioProhibidoGlobal::muletillasDeTextoIa()[0], $directrices );
	}

	public function test_incluye_lineas_rojas_y_rasgos_de_voz_y_extension_objetivo(): void {
		$directrices = ( new CompiladorDirectrices() )->compilar(
			$this->periodista( 40 ),
			Tono::Analitico,
			Tono::Critico,
			NivelSatiraPermitida::No
		);

		self::assertStringContainsString( 'menores de edad', $directrices );
		self::assertStringContainsString( 'abre con una pregunta retórica', $directrices );
		self::assertStringContainsString( 'aproximadamente', $directrices );
	}
}
