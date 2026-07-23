<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Pipeline;

use Brain\Monkey\Functions;
use Mockery;
use Pluma\Datos\RepositorioAuditoriaInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\GestorSalaTendencias;
use Pluma\Pipeline\Pieza;
use Pluma\Pipeline\TendenciaNoEncontradaException;
use Pluma\Pipeline\Transicionador;
use Pluma\Sensores\EstadoTendencia;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * Sala de Tendencias (Libro Cap. 10.2): las tres acciones directas sobre la
 * agenda. Semántica del propietario (2026-07-23): ignorar/vigilar descartan
 * la Pieza en curso; cubrir prioriza o crea con prioridad.
 *
 * @covers \Pluma\Pipeline\GestorSalaTendencias
 */
final class GestorSalaTendenciasTest extends CasoDePruebaUnitario {

	private function pieza( int $id, EstadoPieza $estado ): Pieza {
		$reloj = new RelojFijo();

		return new Pieza( $id, 100, $estado, null, null, $reloj->ahora(), $reloj->ahora() );
	}

	/**
	 * @param RepositorioPiezasInterface&Mockery\MockInterface $piezas
	 */
	private function construir( $tendencias, $piezas ): GestorSalaTendencias {
		$auditoria = Mockery::mock( RepositorioAuditoriaInterface::class );
		$auditoria->allows( 'registrar' );

		return new GestorSalaTendencias(
			$tendencias,
			$piezas,
			new Transicionador( $piezas, $auditoria, new RelojFijo() ),
			new RelojFijo()
		);
	}

	public function test_cubrir_ahora_prioriza_la_pieza_viva_y_devuelve_la_tendencia_al_pipeline(): void {
		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'actualizarEstadoTendencia' )->with( 5, EstadoTendencia::EnPipeline )->andReturn( true );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerUltimaPorTendencia' )->with( 5 )->andReturn( $this->pieza( 40, EstadoPieza::Detectada ) );
		$piezas->expects( 'priorizar' )->with( 40, Mockery::any() )->andReturn( true );
		$piezas->expects( 'crear' )->never();

		$this->construir( $tendencias, $piezas )->cubrirAhora( 5 );

		$this->expectNotToPerformAssertions();
	}

	public function test_cubrir_ahora_crea_una_pieza_nueva_prioritaria_si_la_anterior_fue_descartada(): void {
		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'actualizarEstadoTendencia' )->with( 6, EstadoTendencia::EnPipeline )->andReturn( true );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerUltimaPorTendencia' )->with( 6 )->andReturn( $this->pieza( 41, EstadoPieza::Descartada ) );
		$piezas->expects( 'crear' )->with( 6, Mockery::any() )->andReturn( 42 );
		$piezas->expects( 'priorizar' )->with( 42, Mockery::any() )->andReturn( true );

		$this->construir( $tendencias, $piezas )->cubrirAhora( 6 );

		$this->expectNotToPerformAssertions();
	}

	public function test_vigilar_descarta_la_pieza_en_curso_y_marca_la_tendencia(): void {
		Functions\when( 'do_action' )->justReturn( null );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'actualizarEstadoTendencia' )->with( 7, EstadoTendencia::Vigilada )->andReturn( true );

		$pieza  = $this->pieza( 50, EstadoPieza::Detectada );
		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerUltimaPorTendencia' )->with( 7 )->andReturn( $pieza );
		$piezas->allows( 'obtenerPorId' )->with( 50 )->andReturn( $pieza );
		$piezas->expects( 'actualizarEstado' )->with( 50, EstadoPieza::Detectada, EstadoPieza::Descartada, Mockery::any() )->andReturn( true );

		$this->construir( $tendencias, $piezas )->vigilar( 7 );

		$this->expectNotToPerformAssertions();
	}

	public function test_ignorar_una_tendencia_cuya_pieza_ya_se_publico_no_toca_la_pieza(): void {
		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'actualizarEstadoTendencia' )->with( 8, EstadoTendencia::Ignorada )->andReturn( true );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerUltimaPorTendencia' )->with( 8 )->andReturn( $this->pieza( 60, EstadoPieza::Publicada ) );
		$piezas->expects( 'actualizarEstado' )->never();

		$this->construir( $tendencias, $piezas )->ignorar( 8 );

		$this->expectNotToPerformAssertions();
	}

	public function test_una_tendencia_inexistente_lanza_excepcion(): void {
		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->allows( 'actualizarEstadoTendencia' )->andReturn( false );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );

		$this->expectException( TendenciaNoEncontradaException::class );

		$this->construir( $tendencias, $piezas )->cubrirAhora( 999 );
	}
}
