<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Brain\Monkey\Functions;
use Pluma\Kernel\Desactivador;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * @covers \Pluma\Kernel\Desactivador
 */
final class DesactivadorTest extends CasoDePruebaUnitario {

	public function test_desactivar_limpia_el_aviso_de_cron_y_no_borra_datos_del_cliente(): void {
		Functions\expect( 'delete_transient' )
			->once()
			->with( Desactivador::AVISO_CRON_TRANSIENT )
			->andReturn( true );

		Functions\expect( 'update_option' )
			->once()
			->with( Desactivador::OPCION_DESACTIVADO_EN, '2026-07-22T12:00:00+00:00', false )
			->andReturn( true );

		// Ninguna expectativa de delete_option: la desactivación jamás borra
		// opciones de datos del cliente (solo la desinstalación explícita lo hace).
		Functions\expect( 'delete_option' )->never();

		Desactivador::desactivar( new RelojFijo() );

		$this->expectNotToPerformAssertions();
	}
}
