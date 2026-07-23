<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Mockery;
use Pluma\Admin\NotificadorRevision;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Notificación por correo de la Sala de Revisión (Libro Cap. 10.2):
 * decisión del propietario, solo `wp_mail` por ahora.
 *
 * @covers \Pluma\Admin\NotificadorRevision
 */
final class NotificadorRevisionTest extends CasoDePruebaUnitario {

	public function test_notifica_al_correo_del_administrador_con_el_motivo(): void {
		Functions\when( 'get_option' )->justReturn( 'editor@example.com' );
		Functions\when( '__' )->returnArg( 1 );

		Functions\expect( 'wp_mail' )
			->once()
			->with(
				'editor@example.com',
				Mockery::on( static fn ( string $asunto ): bool => str_contains( $asunto, '#42' ) ),
				Mockery::on( static fn ( string $cuerpo ): bool => str_contains( $cuerpo, 'riesgo de difamación' ) )
			)
			->andReturn( true );

		( new NotificadorRevision() )->notificarRetenida( 42, EstadoPieza::EnRevision, 'riesgo de difamación' );

		$this->expectNotToPerformAssertions();
	}

	public function test_no_notifica_si_no_hay_correo_de_administrador_configurado(): void {
		Functions\when( 'get_option' )->justReturn( '' );

		Functions\expect( 'wp_mail' )->never();

		( new NotificadorRevision() )->notificarRetenida( 1, EstadoPieza::EnRevision, 'x' );

		$this->expectNotToPerformAssertions();
	}
}
