<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Brain\Monkey\Functions;
use Mockery;
use Pluma\Datos\Migrador;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Desactivador;
use Pluma\Kernel\Desinstalador;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Kernel\Desinstalador
 */
final class DesinstaladorTest extends CasoDePruebaUnitario {

	public function test_purgar_revoca_capacidades_y_borra_solo_las_opciones_propias_del_nucleo(): void {
		$rolEditor = Mockery::mock( 'WP_Role' );
		$rolEditor->expects( 'remove_cap' )->times( 3 );

		$wpRoles = new class($rolEditor) {
			/** @var array<string, object> */
			public array $role_objects;

			public function __construct( object $editor ) {
				$this->role_objects = array( 'editor' => $editor );
			}
		};
		global $wp_roles;
		$wp_roles = $wpRoles;

		Functions\expect( 'delete_option' )->times( 4 )->with(
			Mockery::anyOf(
				Activador::OPCION_CONSERVAR_DATOS,
				Activador::OPCION_ACTIVADO_EN,
				Desactivador::OPCION_DESACTIVADO_EN,
				Migrador::OPCION_VERSION
			)
		);

		Functions\expect( 'delete_transient' )->once()->with( Desactivador::AVISO_CRON_TRANSIENT );

		Desinstalador::purgar();

		unset( $GLOBALS['wp_roles'] );

		$this->expectNotToPerformAssertions();
	}

	public function test_purgar_admite_opciones_adicionales_de_modulos_futuros(): void {
		global $wp_roles;
		$wp_roles = new class() {
			public array $role_objects = array();
		};

		Functions\expect( 'delete_option' )->times( 5 )->with(
			Mockery::anyOf(
				Activador::OPCION_CONSERVAR_DATOS,
				Activador::OPCION_ACTIVADO_EN,
				Desactivador::OPCION_DESACTIVADO_EN,
				Migrador::OPCION_VERSION,
				'pluma_opcion_de_un_modulo_futuro'
			)
		);
		Functions\expect( 'delete_transient' )->once();

		Desinstalador::purgar( array( 'pluma_opcion_de_un_modulo_futuro' ) );

		unset( $GLOBALS['wp_roles'] );

		$this->expectNotToPerformAssertions();
	}
}
