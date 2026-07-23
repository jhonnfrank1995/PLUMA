<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Taxonomia\AsignadorCategoria;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\ReconciliadorVocabulario;
use Pluma\Taxonomia\TipoVocabulario;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 7.1 — "el Taxónomo jamás crea categorías: solo asigna".
 *
 * @covers \Pluma\Taxonomia\AsignadorCategoria
 */
final class AsignadorCategoriaTest extends CasoDePruebaUnitario {

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

	private function categoria( string $nombre, string $slug ): EntradaVocabulario {
		return new EntradaVocabulario(
			1,
			TipoVocabulario::Categoria,
			$nombre,
			$slug,
			array(),
			false,
			0,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);
	}

	public function test_asigna_la_categoria_existente_que_calza(): void {
		$economia = $this->categoria( 'Economía', 'economia' );

		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->with( TipoVocabulario::Categoria )->willReturn( array( $economia ) );

		$categoriaAsignada = ( new AsignadorCategoria( new ReconciliadorVocabulario(), $repositorio ) )->asignar( 'economia' );

		self::assertSame( 'Economía', $categoriaAsignada );
	}

	public function test_devuelve_null_si_ninguna_categoria_alcanza_el_umbral(): void {
		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->willReturn( array( $this->categoria( 'Deportes', 'deportes' ) ) );

		self::assertNull( ( new AsignadorCategoria( new ReconciliadorVocabulario(), $repositorio ) )->asignar( 'politica exterior' ) );
	}

	public function test_jamas_crea_una_categoria_nueva_ni_siquiera_sin_coincidencia(): void {
		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->willReturn( array() );
		$repositorio->expects( self::never() )->method( 'crear' );

		( new AsignadorCategoria( new ReconciliadorVocabulario(), $repositorio ) )->asignar( 'un tema totalmente nuevo' );
	}
}
