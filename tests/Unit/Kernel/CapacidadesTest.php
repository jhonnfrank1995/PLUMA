<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Brain\Monkey\Functions;
use Mockery;
use Pluma\Kernel\Capacidades;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Kernel\Capacidades
 */
final class CapacidadesTest extends CasoDePruebaUnitario {

	public function test_instalar_concede_las_tres_capacidades_al_rol_administrador(): void {
		$rolAdministrador = Mockery::mock( 'WP_Role' );
		$rolAdministrador->expects( 'add_cap' )->once()->with( Capacidades::GESTIONAR_PERIODISTAS );
		$rolAdministrador->expects( 'add_cap' )->once()->with( Capacidades::APROBAR_PIEZAS );
		$rolAdministrador->expects( 'add_cap' )->once()->with( Capacidades::CONFIGURAR_MOTOR );

		Functions\expect( 'get_role' )->once()->with( 'administrator' )->andReturn( $rolAdministrador );

		Capacidades::instalar();

		$this->expectNotToPerformAssertions();
	}

	public function test_instalar_no_falla_si_el_rol_administrador_no_existe(): void {
		Functions\expect( 'get_role' )->once()->with( 'administrator' )->andReturn( null );

		Capacidades::instalar();

		$this->expectNotToPerformAssertions();
	}

	public function test_desinstalar_revoca_las_capacidades_de_todos_los_roles(): void {
		$rolEditor = Mockery::mock( 'WP_Role' );
		$rolEditor->expects( 'remove_cap' )->times( 3 );

		$rolSuscriptor = Mockery::mock( 'WP_Role' );
		$rolSuscriptor->expects( 'remove_cap' )->times( 3 );

		$wpRoles = new class($rolEditor, $rolSuscriptor) {
			/** @var array<string, object> */
			public array $role_objects;

			public function __construct( object $editor, object $suscriptor ) {
				$this->role_objects = array(
					'editor'     => $editor,
					'subscriber' => $suscriptor,
				);
			}
		};

		global $wp_roles;
		$wp_roles = $wpRoles;

		Capacidades::desinstalar();

		unset( $GLOBALS['wp_roles'] );

		$this->expectNotToPerformAssertions();
	}

	public function test_todas_devuelve_exactamente_las_tres_capacidades_documentadas(): void {
		self::assertSame(
			array(
				Capacidades::GESTIONAR_PERIODISTAS,
				Capacidades::APROBAR_PIEZAS,
				Capacidades::CONFIGURAR_MOTOR,
			),
			Capacidades::todas()
		);
	}
}
