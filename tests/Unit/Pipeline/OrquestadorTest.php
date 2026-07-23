<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Pipeline;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Mockery;
use Pluma\Datos\CandadoGlobalInterface;
use Pluma\Datos\RepositorioAuditoriaInterface;
use Pluma\Datos\RepositorioBitacoraInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\InvestigadorInterface;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\Orquestador;
use Pluma\Pipeline\Pieza;
use Pluma\Pipeline\Transicionador;
use Pluma\Proveedores\ProveedorTendenciasException;
use Pluma\Publicacion\CreadorBorradorInterface;
use Pluma\Redaccion\RedactorInterface;
use Pluma\Redaccion\ResultadoRedaccion;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\SensorInterface;
use Pluma\Sensores\TendenciaDetectada;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * @covers \Pluma\Pipeline\Orquestador
 */
final class OrquestadorTest extends CasoDePruebaUnitario {

	private function pieza( int $id, EstadoPieza $estado, ?Expediente $expediente = null ): Pieza {
		$reloj = new RelojFijo();

		return new Pieza( $id, 100, $estado, $expediente, null, $reloj->ahora(), $reloj->ahora() );
	}

	private function construir(
		CandadoGlobalInterface $candado,
		RepositorioBitacoraInterface $bitacora,
		RepositorioPiezasInterface $piezas,
		RepositorioTendenciasInterface $tendencias,
		Transicionador $transicionador,
		SensorInterface $sensor,
		InvestigadorInterface $investigador,
		RedactorInterface $redactor,
		CreadorBorradorInterface $creadorBorrador
	): Orquestador {
		return new Orquestador(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			$transicionador,
			$sensor,
			$investigador,
			$redactor,
			$creadorBorrador,
			new RelojFijo()
		);
	}

	public function test_si_no_adquiere_el_candado_no_ejecuta_nada(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( false );
		$candado->expects( 'liberar' )->never();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->once()->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' )->once();

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->never();

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$sensor     = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->never();

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador(
				Mockery::mock( RepositorioPiezasInterface::class ),
				Mockery::mock( RepositorioAuditoriaInterface::class ),
				new RelojFijo()
			),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertFalse( $resultado['ejecutado'] );
		self::assertSame( 0, $resultado['lotesProcesados'] );
	}

	public function test_detecta_una_tendencia_nueva_y_crea_su_pieza(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->once()->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' )->once()->with( 1, Mockery::any(), 0, array() );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Detectada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Investigada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'crear' )->once()->with( 55, Mockery::any() )->andReturn( 1 );

		$tendencia = new TendenciaDetectada(
			'tendencia nueva',
			PuntuacionOportunidad::calcular( 80, 80 ),
			new DateTimeImmutable(),
			array(),
			'google_trends'
		);

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'existePorTermino' )->with( 'tendencia nueva', 'google_trends' )->andReturn( false );
		$tendencias->expects( 'guardar' )->once()->andReturn( 55 );

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->once()->andReturn( array( $tendencia ) );
		$sensor->allows( 'nombre' )->andReturn( 'google_trends' );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador(
				Mockery::mock( RepositorioPiezasInterface::class ),
				Mockery::mock( RepositorioAuditoriaInterface::class ),
				new RelojFijo()
			),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertTrue( $resultado['ejecutado'] );
		self::assertSame( array(), $resultado['errores'] );
	}

	public function test_tendencia_ya_existente_no_se_duplica(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->twice()->andReturn( array() );
		$piezas->expects( 'crear' )->never();

		$tendencia = new TendenciaDetectada(
			'ya vista',
			PuntuacionOportunidad::calcular( 50, 50 ),
			new DateTimeImmutable(),
			array(),
			'google_trends'
		);

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'existePorTermino' )->andReturn( true );
		$tendencias->expects( 'guardar' )->never();

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andReturn( array( $tendencia ) );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador(
				Mockery::mock( RepositorioPiezasInterface::class ),
				Mockery::mock( RepositorioAuditoriaInterface::class ),
				new RelojFijo()
			),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$orquestador->ejecutarTick();

		$this->expectNotToPerformAssertions();
	}

	public function test_el_sensor_caido_no_detiene_el_resto_del_tick(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' )->once()->with(
			1,
			Mockery::any(),
			0,
			Mockery::on( static fn ( array $errores ): bool => 1 === count( $errores ) && str_contains( $errores[0], 'google_trends' ) )
		);

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->twice()->andReturn( array() );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andThrow( new ProveedorTendenciasException( 'feed caído' ) );
		$sensor->allows( 'nombre' )->andReturn( 'google_trends' );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador(
				Mockery::mock( RepositorioPiezasInterface::class ),
				Mockery::mock( RepositorioAuditoriaInterface::class ),
				new RelojFijo()
			),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertTrue( $resultado['ejecutado'] );
		self::assertCount( 1, $resultado['errores'] );
	}

	public function test_avanza_una_pieza_detectada_hasta_crear_el_borrador(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' );

		$piezaDetectada = $this->pieza( 7, EstadoPieza::Detectada );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Detectada, Mockery::any() )->andReturn( array( $piezaDetectada ) );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Investigada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'obtenerPorId' )->with( 7 )->twice()->andReturn(
			$piezaDetectada,
			$this->pieza( 7, EstadoPieza::EnInvestigacion )
		);
		$piezas->expects( 'actualizarEstado' )
			->with( 7, EstadoPieza::Detectada, EstadoPieza::EnInvestigacion, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarEstado' )
			->with( 7, EstadoPieza::EnInvestigacion, EstadoPieza::Investigada, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarExpediente' )->once()->andReturn( true );

		$auditoria = Mockery::mock( RepositorioAuditoriaInterface::class );
		$auditoria->expects( 'registrar' )->twice();

		Functions\when( 'do_action' )->justReturn( null );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'existePorTermino' )->never();
		$tendencias->expects( 'obtenerPorId' )->with( 100 )->andReturn(
			array(
				'termino'               => 'una tendencia',
				'articulosRelacionados' => array(),
			)
		);

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andReturn( array() );

		$investigador = Mockery::mock( InvestigadorInterface::class );
		$expediente   = new Expediente( 'una tendencia', array() );
		$investigador->expects( 'investigar' )->with( 'una tendencia', array() )->andReturn( $expediente );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador( $piezas, $auditoria, new RelojFijo() ),
			$sensor,
			$investigador,
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertSame( 1, $resultado['lotesProcesados'] );
		self::assertSame( array(), $resultado['errores'] );
	}

	public function test_una_pieza_investigada_se_redacta_y_crea_el_post_borrador(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' );

		$expediente       = new Expediente( 'una tendencia', array() );
		$piezaInvestigada = $this->pieza( 9, EstadoPieza::Investigada, $expediente );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Detectada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Investigada, Mockery::any() )->andReturn( array( $piezaInvestigada ) );
		$piezas->expects( 'obtenerPorId' )->with( 9 )->twice()->andReturn(
			$piezaInvestigada,
			$this->pieza( 9, EstadoPieza::EnRedaccion, $expediente )
		);
		$piezas->expects( 'actualizarEstado' )
			->with( 9, EstadoPieza::Investigada, EstadoPieza::EnRedaccion, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarEstado' )
			->with( 9, EstadoPieza::EnRedaccion, EstadoPieza::Redactada, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarPostId' )->once()->with( 9, 321, Mockery::any() )->andReturn( true );

		$auditoria = Mockery::mock( RepositorioAuditoriaInterface::class );
		$auditoria->expects( 'registrar' )->twice();

		Functions\when( 'do_action' )->justReturn( null );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andReturn( array() );

		$resultadoRedaccion = new ResultadoRedaccion( 'Titulo', '<p>cuerpo</p>', false, null, 1 );
		$redactor           = Mockery::mock( RedactorInterface::class );
		$redactor->expects( 'redactar' )
			->with( Mockery::on( static fn ( Pieza $p ): bool => 9 === $p->id && EstadoPieza::EnRedaccion === $p->estado && $expediente === $p->expediente ) )
			->andReturn( $resultadoRedaccion );

		$creadorBorrador = Mockery::mock( CreadorBorradorInterface::class );
		$creadorBorrador->expects( 'crear' )->with( 'Titulo', '<p>cuerpo</p>' )->andReturn( 321 );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador( $piezas, $auditoria, new RelojFijo() ),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			$redactor,
			$creadorBorrador
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertSame( 1, $resultado['lotesProcesados'] );
		self::assertSame( array(), $resultado['errores'] );
	}

	public function test_una_pieza_retenida_por_el_corrector_no_crea_borrador_ni_marca_fallida(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' )->once()->with( 1, Mockery::any(), 1, array() );

		$expediente       = new Expediente( 'una tendencia', array() );
		$piezaInvestigada = $this->pieza( 11, EstadoPieza::Investigada, $expediente );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Detectada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Investigada, Mockery::any() )->andReturn( array( $piezaInvestigada ) );
		$piezas->expects( 'obtenerPorId' )->with( 11 )->twice()->andReturn(
			$piezaInvestigada,
			$this->pieza( 11, EstadoPieza::EnRedaccion, $expediente )
		);
		$piezas->expects( 'actualizarEstado' )
			->with( 11, EstadoPieza::Investigada, EstadoPieza::EnRedaccion, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarEstado' )
			->with( 11, EstadoPieza::EnRedaccion, EstadoPieza::Retenida, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarPostId' )->never();

		$auditoria = Mockery::mock( RepositorioAuditoriaInterface::class );
		$auditoria->expects( 'registrar' )->twice();

		Functions\when( 'do_action' )->justReturn( null );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andReturn( array() );

		$resultadoRedaccion = new ResultadoRedaccion( '', '', true, 'El Corrector Interno no aprobó la pieza tras 2 ciclos de revisión.', 2 );
		$redactor           = Mockery::mock( RedactorInterface::class );
		$redactor->expects( 'redactar' )->once()->andReturn( $resultadoRedaccion );

		$creadorBorrador = Mockery::mock( CreadorBorradorInterface::class );
		$creadorBorrador->expects( 'crear' )->never();

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador( $piezas, $auditoria, new RelojFijo() ),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			$redactor,
			$creadorBorrador
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertSame( 1, $resultado['lotesProcesados'] );
		self::assertSame( array(), $resultado['errores'] );
	}

	public function test_un_error_al_investigar_marca_la_pieza_como_fallida(): void {
		$candado = Mockery::mock( CandadoGlobalInterface::class );
		$candado->expects( 'adquirir' )->andReturn( true );
		$candado->expects( 'liberar' )->once();

		$bitacora = Mockery::mock( RepositorioBitacoraInterface::class );
		$bitacora->expects( 'iniciarEjecucion' )->andReturn( 1 );
		$bitacora->expects( 'finalizarEjecucion' )->once()->with(
			1,
			Mockery::any(),
			1,
			Mockery::on( static fn ( array $errores ): bool => 1 === count( $errores ) && str_contains( $errores[0], 'pieza 3' ) )
		);

		$piezaDetectada = $this->pieza( 3, EstadoPieza::Detectada );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Detectada, Mockery::any() )->andReturn( array( $piezaDetectada ) );
		$piezas->expects( 'obtenerPorEstado' )->with( EstadoPieza::Investigada, Mockery::any() )->andReturn( array() );
		$piezas->expects( 'obtenerPorId' )->with( 3 )->twice()->andReturn(
			$piezaDetectada,
			$this->pieza( 3, EstadoPieza::EnInvestigacion )
		);
		$piezas->expects( 'actualizarEstado' )
			->with( 3, EstadoPieza::Detectada, EstadoPieza::EnInvestigacion, Mockery::any() )
			->andReturn( true );
		$piezas->expects( 'actualizarEstado' )
			->with( 3, EstadoPieza::EnInvestigacion, EstadoPieza::Fallida, Mockery::any() )
			->andReturn( true );

		$auditoria = Mockery::mock( RepositorioAuditoriaInterface::class );
		$auditoria->expects( 'registrar' )->twice();

		Functions\when( 'do_action' )->justReturn( null );

		$tendencias = Mockery::mock( RepositorioTendenciasInterface::class );
		$tendencias->expects( 'obtenerPorId' )->with( 100 )->andReturn( null );

		$sensor = Mockery::mock( SensorInterface::class );
		$sensor->expects( 'detectar' )->andReturn( array() );

		$orquestador = $this->construir(
			$candado,
			$bitacora,
			$piezas,
			$tendencias,
			new Transicionador( $piezas, $auditoria, new RelojFijo() ),
			$sensor,
			Mockery::mock( InvestigadorInterface::class ),
			Mockery::mock( RedactorInterface::class ),
			Mockery::mock( CreadorBorradorInterface::class )
		);

		$resultado = $orquestador->ejecutarTick();

		self::assertSame( 1, $resultado['lotesProcesados'] );
		self::assertCount( 1, $resultado['errores'] );
	}
}
