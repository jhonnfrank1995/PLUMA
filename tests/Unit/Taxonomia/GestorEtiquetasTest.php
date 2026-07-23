<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\ExtractorEntidades;
use Pluma\Taxonomia\GestorEtiquetas;
use Pluma\Taxonomia\ReconciliadorVocabulario;
use Pluma\Taxonomia\TipoVocabulario;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * Libro Cap. 7.2 — el algoritmo de etiquetado completo: reconcilia o crea
 * en cuarentena. `ExtractorEntidades`/`ReconciliadorVocabulario` son clases
 * `final`, se usan reales (mismo criterio que `EvaluadorCompuertas`); solo
 * el repositorio (interfaz) se mockea.
 *
 * @covers \Pluma\Taxonomia\GestorEtiquetas
 */
final class GestorEtiquetasTest extends CasoDePruebaUnitario {

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

	private function gestor( RepositorioVocabularioInterface $repositorio ): GestorEtiquetas {
		return new GestorEtiquetas( new ExtractorEntidades(), new ReconciliadorVocabulario(), $repositorio, new RelojFijo() );
	}

	private function hecho( string $extracto ): HechoFuente {
		return new HechoFuente( $extracto, 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado );
	}

	public function test_reutiliza_una_etiqueta_existente_e_incrementa_su_uso(): void {
		$expediente = new Expediente(
			'x',
			array(
				$this->hecho( 'Microsoft lanzó un producto.' ),
				$this->hecho( 'Microsoft confirmó el lanzamiento.' ),
			)
		);

		$existente = new EntradaVocabulario(
			9,
			TipoVocabulario::Etiqueta,
			'Microsoft',
			'microsoft',
			array(),
			false,
			5,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);

		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->with( TipoVocabulario::Etiqueta )->willReturn( array( $existente ) );
		$repositorio->expects( self::once() )->method( 'incrementarUso' )->with( 9 );
		$repositorio->expects( self::never() )->method( 'crear' );

		$asignadas = $this->gestor( $repositorio )->asignar( $expediente, '' );

		self::assertCount( 1, $asignadas );
		self::assertSame( 9, $asignadas[0]->vocabularioId );
		self::assertFalse( $asignadas[0]->esNueva );
		self::assertFalse( $asignadas[0]->enCuarentena );
	}

	public function test_crea_una_etiqueta_nueva_en_cuarentena_si_no_hay_equivalente(): void {
		$expediente = new Expediente(
			'x',
			array(
				$this->hecho( 'Microsoft lanzó un producto.' ),
				$this->hecho( 'Microsoft confirmó el lanzamiento.' ),
			)
		);

		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->willReturn( array() );
		$repositorio->expects( self::never() )->method( 'incrementarUso' );
		$repositorio->expects( self::once() )->method( 'crear' )
			->with( TipoVocabulario::Etiqueta, 'Microsoft', 'microsoft', array(), true )
			->willReturn( 42 );

		$asignadas = $this->gestor( $repositorio )->asignar( $expediente, '' );

		self::assertCount( 1, $asignadas );
		self::assertSame( 42, $asignadas[0]->vocabularioId );
		self::assertTrue( $asignadas[0]->esNueva );
		self::assertTrue( $asignadas[0]->enCuarentena );
	}

	public function test_menos_de_tres_entidades_no_es_un_error(): void {
		$expediente = new Expediente( 'x', array( $this->hecho( 'esto esta entre sobre desde hasta.' ) ) );

		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->willReturn( array() );

		self::assertSame( array(), $this->gestor( $repositorio )->asignar( $expediente, '' ) );
	}
}
