<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Taxonomia\AsignadorCategoria;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\ExtractorEntidades;
use Pluma\Taxonomia\GestorEtiquetas;
use Pluma\Taxonomia\ReconciliadorVocabulario;
use Pluma\Taxonomia\Taxonomo;
use Pluma\Taxonomia\TipoVocabulario;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * Único punto de entrada de `Pluma\Taxonomia` (Libro Cap. 7): orquesta
 * asignación de categoría + etiquetado en una sola llamada.
 *
 * @covers \Pluma\Taxonomia\Taxonomo
 */
final class TaxonomoTest extends CasoDePruebaUnitario {

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

	public function test_clasificar_combina_categoria_y_etiquetas(): void {
		$expediente = new Expediente(
			'x',
			array(
				new HechoFuente( 'Microsoft lanzó un producto.', 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ),
				new HechoFuente( 'Microsoft confirmó el lanzamiento.', 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ),
			)
		);

		$economia = new EntradaVocabulario(
			1,
			TipoVocabulario::Categoria,
			'Economía',
			'economia',
			array(),
			false,
			0,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);

		$repositorio = $this->createMock( RepositorioVocabularioInterface::class );
		$repositorio->method( 'obtenerPorTipo' )->willReturnCallback(
			static fn ( TipoVocabulario $tipo ) => TipoVocabulario::Categoria === $tipo ? array( $economia ) : array()
		);
		$repositorio->method( 'crear' )->willReturn( 77 );

		$reconciliador = new ReconciliadorVocabulario();
		$taxonomo      = new Taxonomo(
			new AsignadorCategoria( $reconciliador, $repositorio ),
			new GestorEtiquetas( new ExtractorEntidades(), $reconciliador, $repositorio, new RelojFijo() )
		);

		$resultado = $taxonomo->clasificar( $expediente, 'economia', '' );

		self::assertSame( 'Economía', $resultado->categoriaAsignada );
		self::assertCount( 1, $resultado->etiquetas );
		self::assertSame( 'Microsoft', $resultado->etiquetas[0]->nombre );
	}
}
