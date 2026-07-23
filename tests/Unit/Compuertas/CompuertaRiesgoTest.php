<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use DateTimeImmutable;
use Pluma\Compuertas\CompuertaException;
use Pluma\Compuertas\CompuertaRiesgo;
use Pluma\Compuertas\TemaRegulado;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Redaccion\ClasificacionNoticia;
use Pluma\Redaccion\NovedadNoticia;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\ProveedorLenguajeFalso;

/**
 * Libro Cap. 8.2 — Compuerta de Riesgo.
 *
 * @covers \Pluma\Compuertas\CompuertaRiesgo
 */
final class CompuertaRiesgoTest extends CasoDePruebaUnitario {

	private function expediente(): Expediente {
		return new Expediente(
			'x',
			array( new HechoFuente( 'un hecho verificado', 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ) )
		);
	}

	private function clasificacion( TipoNoticia $tipo = TipoNoticia::DatoEconomico ): ClasificacionNoticia {
		return new ClasificacionNoticia( 'economia', 30, 'x', NovedadNoticia::Primicia, 50, $tipo );
	}

	public function test_hereda_implica_tragedia_de_la_clasificacion_sin_preguntarle_al_modelo(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"implicaMenores": false, "implicaSalud": false, "implicaViolencia": false, "riesgoDifamacion": false, "detalleDifamacion": "", "hechosDisputadosSinSenalar": false, "temaRegulado": null}'
		);

		$diagnostico = ( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion( TipoNoticia::Tragedia ) );

		self::assertTrue( $diagnostico->implicaTragedia );
		self::assertTrue( $diagnostico->requiereDegradacionPorSensibilidad() );
	}

	public function test_interpreta_riesgo_de_difamacion(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"implicaMenores": false, "implicaSalud": false, "implicaViolencia": false, "riesgoDifamacion": true, "detalleDifamacion": "acusa de fraude sin doble fuente verificada", "hechosDisputadosSinSenalar": false, "temaRegulado": null}'
		);

		$diagnostico = ( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion() );

		self::assertTrue( $diagnostico->riesgoDifamacion );
		self::assertTrue( $diagnostico->requiereRetencionParaHumano() );
		self::assertSame( 'acusa de fraude sin doble fuente verificada', $diagnostico->detalleDifamacion );
	}

	public function test_interpreta_un_tema_regulado(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"implicaMenores": false, "implicaSalud": false, "implicaViolencia": false, "riesgoDifamacion": false, "detalleDifamacion": "", "hechosDisputadosSinSenalar": false, "temaRegulado": "salud"}'
		);

		$diagnostico = ( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion() );

		self::assertSame( TemaRegulado::Salud, $diagnostico->temaRegulado );
	}

	public function test_lanza_excepcion_si_el_temaRegulado_es_desconocido(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"implicaMenores": false, "implicaSalud": false, "implicaViolencia": false, "riesgoDifamacion": false, "detalleDifamacion": "", "hechosDisputadosSinSenalar": false, "temaRegulado": "astrologia"}'
		);

		$this->expectException( CompuertaException::class );

		( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion() );
	}

	public function test_lanza_excepcion_si_falta_un_campo_en_la_respuesta(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"implicaMenores": false}' );

		$this->expectException( CompuertaException::class );

		( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion() );
	}

	public function test_lanza_excepcion_si_la_respuesta_llego_truncada(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"implicaMenores": false, "implicaSalud": false, "implicaViolencia": false, "riesgoDifamacion": false, "detalleDifamacion": "", "hechosDisputadosSinSenalar": false, "temaRegulado": null}',
			truncada: true
		);

		$this->expectException( CompuertaException::class );

		( new CompuertaRiesgo( $proveedor ) )->evaluar( $this->expediente(), 'texto', $this->clasificacion() );
	}
}
