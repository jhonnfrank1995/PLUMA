<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\ReconciliadorVocabulario;
use Pluma\Taxonomia\TipoVocabulario;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 7.2, punto 2 — "si existe, se reutiliza siempre": coincidencia
 * exacta, alias conocidos, y similitud (evita fragmentación como
 * "elecciones2026"/"elecciones-2026").
 *
 * @covers \Pluma\Taxonomia\ReconciliadorVocabulario
 */
final class ReconciliadorVocabularioTest extends CasoDePruebaUnitario {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'sanitize_title' )->alias(
			static function ( string $texto ): string {
				$slug = strtolower( $texto );
				$slug = (string) preg_replace( '/[^a-z0-9]+/', '-', $slug );

				return trim( $slug, '-' );
			}
		);
	}

	/**
	 * @param list<string> $alias
	 */
	private function entrada( string $nombre, string $slug, array $alias = array() ): EntradaVocabulario {
		return new EntradaVocabulario(
			1,
			TipoVocabulario::Etiqueta,
			$nombre,
			$slug,
			$alias,
			false,
			1,
			new DateTimeImmutable( '2026-07-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-07-01T00:00:00+00:00' )
		);
	}

	public function test_reutiliza_por_coincidencia_exacta_de_slug(): void {
		$existente = $this->entrada( 'elecciones2026', 'elecciones2026' );

		$resultado = ( new ReconciliadorVocabulario() )->reconciliar( 'Elecciones2026', array( $existente ) );

		self::assertSame( $existente, $resultado );
	}

	public function test_reutiliza_por_alias_conocido(): void {
		$existente = $this->entrada( 'Inteligencia Artificial', 'inteligencia-artificial', array( 'IA' ) );

		$resultado = ( new ReconciliadorVocabulario() )->reconciliar( 'IA', array( $existente ) );

		self::assertSame( $existente, $resultado );
	}

	public function test_reutiliza_por_similitud_evitando_fragmentacion(): void {
		$existente = $this->entrada( 'elecciones 2026', 'elecciones-2026' );

		$resultado = ( new ReconciliadorVocabulario() )->reconciliar( 'eleccion 2026', array( $existente ) );

		self::assertSame( $existente, $resultado );
	}

	public function test_no_reconcilia_entidades_realmente_distintas(): void {
		$existente = $this->entrada( 'Banco de la Republica', 'banco-de-la-republica' );

		$resultado = ( new ReconciliadorVocabulario() )->reconciliar( 'Ministerio de Hacienda', array( $existente ) );

		self::assertNull( $resultado );
	}

	public function test_sin_candidatos_no_hay_nada_que_reconciliar(): void {
		self::assertNull( ( new ReconciliadorVocabulario() )->reconciliar( 'Cualquier Cosa', array() ) );
	}
}
