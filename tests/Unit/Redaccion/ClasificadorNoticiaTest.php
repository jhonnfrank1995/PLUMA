<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Redaccion\ClasificadorNoticia;
use Pluma\Redaccion\DecisionEditorialException;
use Pluma\Redaccion\NovedadNoticia;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\ProveedorLenguajeFalso;

/**
 * Paso 1 del Algoritmo de Decisión Editorial (Libro Cap. 5.5).
 *
 * @covers \Pluma\Redaccion\ClasificadorNoticia
 */
final class ClasificadorNoticiaTest extends CasoDePruebaUnitario {

	private function expediente(): Expediente {
		return new Expediente(
			'una tendencia',
			array( new HechoFuente( 'un hecho', 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ) )
		);
	}

	public function test_interpreta_una_respuesta_valida(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tema": "economia", "gravedad": 30, "polaridad": "gobierno vs oposición", "novedad": "primicia", "potencialConversacional": 70, "tipoNoticia": "dato_economico"}'
		);

		$clasificacion = ( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );

		self::assertSame( 'economia', $clasificacion->tema );
		self::assertSame( 30, $clasificacion->gravedad );
		self::assertSame( NovedadNoticia::Primicia, $clasificacion->novedad );
		self::assertSame( 70, $clasificacion->potencialConversacional );
		self::assertSame( TipoNoticia::DatoEconomico, $clasificacion->tipoNoticia );
	}

	public function test_recorta_gravedad_y_potencial_conversacional_al_rango_0_100(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tema": "economia", "gravedad": 150, "polaridad": "x", "novedad": "primicia", "potencialConversacional": -10, "tipoNoticia": "dato_economico"}'
		);

		$clasificacion = ( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );

		self::assertSame( 100, $clasificacion->gravedad );
		self::assertSame( 0, $clasificacion->potencialConversacional );
	}

	public function test_lanza_excepcion_si_falta_un_eje(): void {
		$proveedor = new ProveedorLenguajeFalso( '{"tema": "economia", "gravedad": 30}' );

		$this->expectException( DecisionEditorialException::class );

		( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );
	}

	public function test_lanza_excepcion_si_el_tipo_de_noticia_es_un_valor_desconocido(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tema": "economia", "gravedad": 30, "polaridad": "x", "novedad": "primicia", "potencialConversacional": 50, "tipoNoticia": "no_existe"}'
		);

		$this->expectException( DecisionEditorialException::class );

		( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );
	}

	public function test_lanza_excepcion_si_la_respuesta_llego_truncada(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tema": "economia", "gravedad": 30, "polaridad": "x", "novedad": "primicia", "potencialConversacional": 50, "tipoNoticia": "dato_economico"}',
			truncada: true
		);

		$this->expectException( DecisionEditorialException::class );

		( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );
	}

	public function test_el_material_enviado_incluye_los_hechos_del_expediente(): void {
		$proveedor = new ProveedorLenguajeFalso(
			'{"tema": "economia", "gravedad": 30, "polaridad": "x", "novedad": "primicia", "potencialConversacional": 50, "tipoNoticia": "dato_economico"}'
		);

		( new ClasificadorNoticia( $proveedor ) )->clasificar( $this->expediente() );

		self::assertNotNull( $proveedor->ultimaPeticion );
		self::assertStringContainsString( 'un hecho', $proveedor->ultimaPeticion->material );
	}
}
