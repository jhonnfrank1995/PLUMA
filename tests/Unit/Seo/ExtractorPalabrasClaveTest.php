<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Seo\ExtractorPalabrasClave;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 6.2 — la palabra clave principal es siempre la tendencia
 * origen (el Radar ya sabe qué busca la gente); las secundarias son una
 * heurística de frecuencia sobre los extractos del expediente.
 *
 * @covers \Pluma\Seo\ExtractorPalabrasClave
 */
final class ExtractorPalabrasClaveTest extends CasoDePruebaUnitario {

	private function hecho( string $extracto ): HechoFuente {
		return new HechoFuente( $extracto, 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado );
	}

	public function test_la_principal_es_siempre_la_tendencia_origen(): void {
		$expediente = new Expediente( 'reforma pensional', array() );

		$palabras = ( new ExtractorPalabrasClave() )->extraer( $expediente );

		self::assertSame( 'reforma pensional', $palabras->principal );
	}

	public function test_las_secundarias_se_ordenan_por_frecuencia(): void {
		$expediente = new Expediente(
			'x',
			array(
				$this->hecho( 'el gobierno anuncia cambios en el sistema de pensiones' ),
				$this->hecho( 'el sistema de pensiones enfrenta críticas del sistema financiero' ),
				$this->hecho( 'expertos analizan el impacto del sistema en jubilados' ),
			)
		);

		$palabras = ( new ExtractorPalabrasClave() )->extraer( $expediente );

		self::assertSame( 'sistema', $palabras->secundarias[0] );
	}

	public function test_excluye_palabras_cortas_y_palabras_vacias(): void {
		$expediente = new Expediente(
			'x',
			array( $this->hecho( 'esto esta entre sobre desde hasta donde cuando porque tambien mientras aunque todo otro este ese' ) )
		);

		$palabras = ( new ExtractorPalabrasClave() )->extraer( $expediente );

		self::assertSame( array(), $palabras->secundarias );
	}

	public function test_no_repite_palabras_ya_presentes_en_la_principal(): void {
		$expediente = new Expediente(
			'pensiones',
			array( $this->hecho( 'las pensiones generan debate sobre pensiones y jubilación' ) )
		);

		$palabras = ( new ExtractorPalabrasClave() )->extraer( $expediente );

		self::assertNotContains( 'pensiones', $palabras->secundarias );
	}

	public function test_limita_a_un_maximo_de_cinco_secundarias(): void {
		$expediente = new Expediente(
			'x',
			array( $this->hecho( 'alfa beta gamma delta epsilon zeta eta theta iota kappa' ) )
		);

		$palabras = ( new ExtractorPalabrasClave() )->extraer( $expediente );

		self::assertLessThanOrEqual( 5, count( $palabras->secundarias ) );
	}
}
