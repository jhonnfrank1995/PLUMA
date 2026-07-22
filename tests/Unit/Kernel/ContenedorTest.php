<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Pluma\Kernel\Contenedor;
use Pluma\Kernel\Excepciones\ServicioNoRegistradoException;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Kernel\Contenedor
 */
final class ContenedorTest extends CasoDePruebaUnitario {

	public function test_obtiene_lo_que_la_fabrica_registrada_construye(): void {
		$contenedor = new Contenedor();
		$contenedor->registrar( 'saludo', static fn (): string => 'hola' );

		self::assertSame( 'hola', $contenedor->obtener( 'saludo' ) );
	}

	public function test_es_singleton_la_misma_instancia_en_llamadas_sucesivas(): void {
		$contenedor = new Contenedor();
		$contenedor->registrar( 'objeto', static fn (): object => new \stdClass() );

		self::assertSame( $contenedor->obtener( 'objeto' ), $contenedor->obtener( 'objeto' ) );
	}

	public function test_la_fabrica_recibe_el_propio_contenedor_para_resolver_dependencias(): void {
		$contenedor = new Contenedor();
		$contenedor->registrar( 'base', static fn (): int => 40 );
		$contenedor->registrar( 'derivado', static fn ( Contenedor $c ): int => 2 + (int) $c->obtener( 'base' ) );

		self::assertSame( 42, $contenedor->obtener( 'derivado' ) );
	}

	public function test_tiene_informa_si_un_id_esta_registrado(): void {
		$contenedor = new Contenedor();

		self::assertFalse( $contenedor->tiene( 'inexistente' ) );

		$contenedor->registrar( 'inexistente', static fn (): null => null );

		self::assertTrue( $contenedor->tiene( 'inexistente' ) );
	}

	public function test_lanza_excepcion_al_pedir_un_servicio_no_registrado(): void {
		$contenedor = new Contenedor();

		$this->expectException( ServicioNoRegistradoException::class );
		$this->expectExceptionMessage( 'pluma_servicio_fantasma' );

		$contenedor->obtener( 'pluma_servicio_fantasma' );
	}

	public function test_re_registrar_un_id_descarta_la_instancia_previa(): void {
		$contenedor = new Contenedor();
		$contenedor->registrar( 'valor', static fn (): int => 1 );
		self::assertSame( 1, $contenedor->obtener( 'valor' ) );

		$contenedor->registrar( 'valor', static fn (): int => 2 );
		self::assertSame( 2, $contenedor->obtener( 'valor' ) );
	}
}
