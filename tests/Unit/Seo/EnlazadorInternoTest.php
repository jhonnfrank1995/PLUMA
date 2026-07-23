<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Datos\RepositorioMemoriaEditorialInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\Pieza;
use Pluma\Redaccion\EntradaMemoria;
use Pluma\Redaccion\TipoMemoria;
use Pluma\Seo\EnlazadorInterno;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 6.2 — "la memoria de cobertura del periodista es la fuente
 * perfecta" para el enlazado interno automático.
 *
 * @covers \Pluma\Seo\EnlazadorInterno
 */
final class EnlazadorInternoTest extends CasoDePruebaUnitario {

	private function pieza( int $id, EstadoPieza $estado, ?int $postId ): Pieza {
		return new Pieza(
			$id,
			1,
			$estado,
			null,
			$postId,
			new DateTimeImmutable( '2026-07-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-07-01T00:00:00+00:00' )
		);
	}

	private function postura( ?int $piezaId ): EntradaMemoria {
		return new EntradaMemoria( 1, 5, TipoMemoria::Cobertura, 'economia', array(), $piezaId, new DateTimeImmutable( '2026-07-01T00:00:00+00:00' ) );
	}

	public function test_sugiere_enlaces_solo_a_piezas_publicadas_con_post_id(): void {
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/pieza-2' );
		Functions\when( 'get_the_title' )->justReturn( 'Título de la pieza 2' );

		$repoMemoria = $this->createMock( RepositorioMemoriaEditorialInterface::class );
		$repoMemoria->method( 'obtenerPosturasPorTema' )->willReturn( array( $this->postura( 2 ) ) );

		$repoPiezas = $this->createMock( RepositorioPiezasInterface::class );
		$repoPiezas->method( 'obtenerPorId' )->with( 2 )->willReturn( $this->pieza( 2, EstadoPieza::Publicada, 42 ) );

		$enlaces = ( new EnlazadorInterno( $repoMemoria, $repoPiezas ) )->sugerir( 5, 'economia', 1 );

		self::assertCount( 1, $enlaces );
		self::assertSame( 42, $enlaces[0]->postId );
		self::assertSame( 'https://example.com/pieza-2', $enlaces[0]->url );
		self::assertSame( 'Título de la pieza 2', $enlaces[0]->titulo );
	}

	public function test_omite_posturas_sin_pieza_asociada(): void {
		$repoMemoria = $this->createMock( RepositorioMemoriaEditorialInterface::class );
		$repoMemoria->method( 'obtenerPosturasPorTema' )->willReturn( array( $this->postura( null ) ) );

		$repoPiezas = $this->createMock( RepositorioPiezasInterface::class );
		$repoPiezas->expects( self::never() )->method( 'obtenerPorId' );

		$enlaces = ( new EnlazadorInterno( $repoMemoria, $repoPiezas ) )->sugerir( 5, 'economia', 1 );

		self::assertSame( array(), $enlaces );
	}

	public function test_omite_la_pieza_actual_para_no_auto_enlazarse(): void {
		$repoMemoria = $this->createMock( RepositorioMemoriaEditorialInterface::class );
		$repoMemoria->method( 'obtenerPosturasPorTema' )->willReturn( array( $this->postura( 1 ) ) );

		$repoPiezas = $this->createMock( RepositorioPiezasInterface::class );
		$repoPiezas->expects( self::never() )->method( 'obtenerPorId' );

		$enlaces = ( new EnlazadorInterno( $repoMemoria, $repoPiezas ) )->sugerir( 5, 'economia', 1 );

		self::assertSame( array(), $enlaces );
	}

	public function test_omite_piezas_que_no_estan_publicadas(): void {
		$repoMemoria = $this->createMock( RepositorioMemoriaEditorialInterface::class );
		$repoMemoria->method( 'obtenerPosturasPorTema' )->willReturn( array( $this->postura( 2 ) ) );

		$repoPiezas = $this->createMock( RepositorioPiezasInterface::class );
		$repoPiezas->method( 'obtenerPorId' )->willReturn( $this->pieza( 2, EstadoPieza::Redactada, null ) );

		$enlaces = ( new EnlazadorInterno( $repoMemoria, $repoPiezas ) )->sugerir( 5, 'economia', 1 );

		self::assertSame( array(), $enlaces );
	}

	public function test_cumpleMinimo_es_falso_por_debajo_de_dos_enlaces(): void {
		self::assertFalse( ( new EnlazadorInterno( $this->createMock( RepositorioMemoriaEditorialInterface::class ), $this->createMock( RepositorioPiezasInterface::class ) ) )->cumpleMinimo( array() ) );
	}
}
