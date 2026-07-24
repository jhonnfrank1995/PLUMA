<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Pipeline;

use DateTimeImmutable;
use Mockery;
use Pluma\Datos\RepositorioPeriodistasInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioRespuestasComentariosInterface;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\GestorRespuestasComentarios;
use Pluma\Pipeline\Pieza;
use Pluma\Pipeline\RespuestaComentarioEstadoInvalidoException;
use Pluma\Pipeline\RespuestaComentarioNoEncontradaException;
use Pluma\Publicacion\PublicadorComentarioInterface;
use Pluma\Redaccion\ConductaVersion;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\Periodista;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RespuestaComentario;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * Sala de Comentarios (Libro Cap. 5.7, "el editor aprueba con un clic").
 *
 * @covers \Pluma\Pipeline\GestorRespuestasComentarios
 */
final class GestorRespuestasComentariosTest extends CasoDePruebaUnitario {

	private function respuestaPendiente( int $id = 1 ): RespuestaComentario {
		$reloj = new RelojFijo();

		return new RespuestaComentario( $id, 30, 999, 5, 'Gracias por tu comentario, pero...', EstadoRespuestaComentario::PendienteAprobacion, null, $reloj->ahora(), null );
	}

	private function pieza( int $postId = 987 ): Pieza {
		$reloj = new RelojFijo();

		return new Pieza( 30, 100, EstadoPieza::Publicada, null, $postId, $reloj->ahora(), $reloj->ahora() );
	}

	private function periodista(): Periodista {
		$diales   = new Diales( 80, 55, 40, 55, 75, 60, 60, 65 );
		$reglas   = new ReglasConducta( 'linea', array(), array(), array(), TratamientoLector::Tu, '¿Y tú?' );
		$matriz   = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
		);
		$conducta = new ConductaVersion( 1, 5, $diales, $reglas, $matriz, true, new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ) );

		return new Periodista(
			5,
			'Valentina Ruiz',
			null,
			'Bio.',
			RolPeriodista::Columnista,
			array(),
			EstadoPeriodista::Activo,
			$conducta,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);
	}

	public function test_aprobar_publica_el_comentario_real_y_marca_la_respuesta_resuelta(): void {
		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPorId' )->with( 1 )->andReturn( $this->respuestaPendiente() );
		$respuestas->expects( 'marcarResuelta' )->with( 1, EstadoRespuestaComentario::Aprobado, 4321, Mockery::any() )->andReturn( true );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorId' )->with( 30 )->andReturn( $this->pieza() );

		$periodistas = Mockery::mock( RepositorioPeriodistasInterface::class );
		$periodistas->expects( 'obtenerPorId' )->with( 5 )->andReturn( $this->periodista() );

		$publicador = Mockery::mock( PublicadorComentarioInterface::class );
		$publicador->expects( 'publicar' )->with( 987, 999, 'Valentina Ruiz', 'Gracias por tu comentario, pero...' )->andReturn( 4321 );

		( new GestorRespuestasComentarios( $respuestas, $piezas, $periodistas, $publicador, new RelojFijo() ) )->aprobar( 1 );

		$this->expectNotToPerformAssertions();
	}

	public function test_descartar_marca_la_respuesta_sin_publicar_nada(): void {
		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPorId' )->with( 1 )->andReturn( $this->respuestaPendiente() );
		$respuestas->expects( 'marcarResuelta' )->with( 1, EstadoRespuestaComentario::Descartado, null, Mockery::any() )->andReturn( true );

		$publicador = Mockery::mock( PublicadorComentarioInterface::class );
		$publicador->expects( 'publicar' )->never();

		( new GestorRespuestasComentarios(
			$respuestas,
			Mockery::mock( RepositorioPiezasInterface::class ),
			Mockery::mock( RepositorioPeriodistasInterface::class ),
			$publicador,
			new RelojFijo()
		) )->descartar( 1 );

		$this->expectNotToPerformAssertions();
	}

	public function test_aprobar_una_respuesta_inexistente_lanza_excepcion(): void {
		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPorId' )->with( 999 )->andReturn( null );

		$this->expectException( RespuestaComentarioNoEncontradaException::class );

		( new GestorRespuestasComentarios(
			$respuestas,
			Mockery::mock( RepositorioPiezasInterface::class ),
			Mockery::mock( RepositorioPeriodistasInterface::class ),
			Mockery::mock( PublicadorComentarioInterface::class ),
			new RelojFijo()
		) )->aprobar( 999 );
	}

	public function test_aprobar_una_respuesta_ya_resuelta_lanza_excepcion(): void {
		$reloj    = new RelojFijo();
		$resuelta = new RespuestaComentario( 1, 30, 999, 5, 'x', EstadoRespuestaComentario::Aprobado, 111, $reloj->ahora(), $reloj->ahora() );

		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPorId' )->with( 1 )->andReturn( $resuelta );

		$publicador = Mockery::mock( PublicadorComentarioInterface::class );
		$publicador->expects( 'publicar' )->never();

		$this->expectException( RespuestaComentarioEstadoInvalidoException::class );

		( new GestorRespuestasComentarios(
			$respuestas,
			Mockery::mock( RepositorioPiezasInterface::class ),
			Mockery::mock( RepositorioPeriodistasInterface::class ),
			$publicador,
			new RelojFijo()
		) )->aprobar( 1 );
	}

	public function test_aprobar_sin_pieza_o_periodista_resolubles_lanza_excepcion(): void {
		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPorId' )->with( 1 )->andReturn( $this->respuestaPendiente() );

		$piezas = Mockery::mock( RepositorioPiezasInterface::class );
		$piezas->expects( 'obtenerPorId' )->with( 30 )->andReturn( null );

		$periodistas = Mockery::mock( RepositorioPeriodistasInterface::class );
		$periodistas->allows( 'obtenerPorId' )->with( 5 )->andReturn( $this->periodista() );

		$publicador = Mockery::mock( PublicadorComentarioInterface::class );
		$publicador->expects( 'publicar' )->never();

		$this->expectException( RespuestaComentarioEstadoInvalidoException::class );

		( new GestorRespuestasComentarios(
			$respuestas,
			$piezas,
			$periodistas,
			$publicador,
			new RelojFijo()
		) )->aprobar( 1 );
	}

	public function test_obtener_pendientes_delega_en_el_repositorio(): void {
		$pendiente = $this->respuestaPendiente();

		$respuestas = Mockery::mock( RepositorioRespuestasComentariosInterface::class );
		$respuestas->expects( 'obtenerPendientes' )->with( 30 )->andReturn( array( $pendiente ) );

		$resultado = ( new GestorRespuestasComentarios(
			$respuestas,
			Mockery::mock( RepositorioPiezasInterface::class ),
			Mockery::mock( RepositorioPeriodistasInterface::class ),
			Mockery::mock( PublicadorComentarioInterface::class ),
			new RelojFijo()
		) )->obtenerPendientes( 30 );

		self::assertSame( array( $pendiente ), $resultado );
	}
}
