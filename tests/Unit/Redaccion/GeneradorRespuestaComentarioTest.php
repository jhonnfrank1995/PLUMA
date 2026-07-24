<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Redaccion\ConductaVersion;
use Pluma\Redaccion\DecisionEditorialException;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\GeneradorRespuestaComentario;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\Periodista;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\ProveedorLenguajeFalso;

/**
 * Respuestas asistidas a comentarios (Libro Cap. 5.7, "el compromiso de respuesta").
 *
 * @covers \Pluma\Redaccion\GeneradorRespuestaComentario
 */
final class GeneradorRespuestaComentarioTest extends CasoDePruebaUnitario {

	private function periodista(): Periodista {
		$diales   = new Diales( 80, 55, 40, 55, 75, 60, 60, 65 );
		$reglas   = new ReglasConducta( 'linea editorial escéptica', array( 'nunca inventar cifras' ), array(), array(), TratamientoLector::Tu, '¿Y tú?' );
		$matriz   = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
		);
		$conducta = new ConductaVersion( 1, 1, $diales, $reglas, $matriz, true, new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ) );

		return new Periodista(
			1,
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

	public function test_devuelve_el_texto_de_una_respuesta_valida(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"respuesta": "Entiendo tu punto, pero los datos dicen otra cosa."}' );

		$respuesta = ( new GeneradorRespuestaComentario( $proveedor ) )->generar( $this->periodista(), 'economia', 'no me creo esas cifras' );

		self::assertSame( 'Entiendo tu punto, pero los datos dicen otra cosa.', $respuesta );
	}

	public function test_lanza_excepcion_si_falta_la_respuesta(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"otroCampo": "x"}' );

		$this->expectException( DecisionEditorialException::class );

		( new GeneradorRespuestaComentario( $proveedor ) )->generar( $this->periodista(), 'economia', 'comentario' );
	}

	public function test_lanza_excepcion_si_la_respuesta_esta_vacia(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"respuesta": "   "}' );

		$this->expectException( DecisionEditorialException::class );

		( new GeneradorRespuestaComentario( $proveedor ) )->generar( $this->periodista(), 'economia', 'comentario' );
	}

	public function test_lanza_excepcion_si_la_respuesta_llego_truncada(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"respuesta": "x"}', truncada: true );

		$this->expectException( DecisionEditorialException::class );

		( new GeneradorRespuestaComentario( $proveedor ) )->generar( $this->periodista(), 'economia', 'comentario' );
	}

	public function test_el_material_enviado_incluye_el_tema_y_el_comentario(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"respuesta": "x"}' );

		( new GeneradorRespuestaComentario( $proveedor ) )->generar( $this->periodista(), 'economia nacional', 'un comentario del lector' );

		self::assertNotNull( $proveedor->ultimaPeticion );
		self::assertStringContainsString( 'economia nacional', $proveedor->ultimaPeticion->material );
		self::assertStringContainsString( 'un comentario del lector', $proveedor->ultimaPeticion->material );
	}
}
