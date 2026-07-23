<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Proveedores;

use Brain\Monkey\Functions;
use Pluma\Proveedores\PresupuestoLenguaje;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use Pluma\Tests\Unit\Dobles\RelojFijo;

/**
 * @covers \Pluma\Proveedores\PresupuestoLenguaje
 */
final class PresupuestoLenguajeTest extends CasoDePruebaUnitario {

	public function test_limite_diario_usa_el_defecto_si_no_hay_opcion_guardada(): void {
		Functions\when( 'get_option' )->justReturn( 5.0 );

		self::assertSame( 5.0, ( new PresupuestoLenguaje( new RelojFijo() ) )->limiteDiarioUsd() );
	}

	public function test_disponible_es_verdadero_cuando_no_hay_gasto_registrado_hoy(): void {
		Functions\when( 'get_option' )->alias(
			// phpcs:ignore WordPress.CodeAnalysis.AssignmentInTernaryCondition.FoundInTernaryCondition -- falso positivo: es una arrow function con `=>`, no una asignación.
			static fn ( string $opcion, $defecto = false ) => PresupuestoLenguaje::OPCION_GASTO === $opcion ? array() : $defecto
		);

		self::assertTrue( ( new PresupuestoLenguaje( new RelojFijo() ) )->disponible() );
	}

	public function test_disponible_es_falso_cuando_el_gasto_de_hoy_alcanza_el_limite(): void {
		Functions\when( 'get_option' )->alias(
			static fn ( string $opcion, $defecto = false ) => match ( $opcion ) {
				PresupuestoLenguaje::OPCION_LIMITE_DIARIO => 5.0,
				PresupuestoLenguaje::OPCION_GASTO => array(
					'dia'   => ( new RelojFijo() )->ahora()->format( 'Y-m-d' ),
					'gasto' => 5.0,
				),
				default => $defecto,
			}
		);

		self::assertFalse( ( new PresupuestoLenguaje( new RelojFijo() ) )->disponible() );
	}

	public function test_un_registro_de_gasto_de_un_dia_anterior_no_cuenta_para_hoy(): void {
		Functions\when( 'get_option' )->alias(
			static fn ( string $opcion, $defecto = false ) => match ( $opcion ) {
				PresupuestoLenguaje::OPCION_LIMITE_DIARIO => 5.0,
				PresupuestoLenguaje::OPCION_GASTO => array(
					'dia'   => '2020-01-01',
					'gasto' => 999.0,
				),
				default => $defecto,
			}
		);

		self::assertSame( 0.0, ( new PresupuestoLenguaje( new RelojFijo() ) )->gastoHoyUsd() );
	}

	public function test_registrar_gasto_acumula_sobre_el_registro_del_dia(): void {
		Functions\when( 'get_option' )->alias(
			static fn ( string $opcion, $defecto = false ) => match ( $opcion ) {
				PresupuestoLenguaje::OPCION_LIMITE_DIARIO => 5.0,
				PresupuestoLenguaje::OPCION_GASTO => array(
					'dia'   => ( new RelojFijo() )->ahora()->format( 'Y-m-d' ),
					'gasto' => 1.0,
				),
				default => $defecto,
			}
		);
		Functions\when( 'get_transient' )->justReturn( false );

		$capturado = null;
		Functions\when( 'update_option' )->alias(
			static function ( string $opcion, $valor ) use ( &$capturado ): bool {
				if ( PresupuestoLenguaje::OPCION_GASTO === $opcion ) {
					$capturado = $valor;
				}

				return true;
			}
		);
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( null );

		( new PresupuestoLenguaje( new RelojFijo() ) )->registrarGasto( 0.5 );

		self::assertIsArray( $capturado );
		self::assertSame( 1.5, $capturado['gasto'] );
	}

	public function test_registrar_gasto_dispara_el_aviso_del_80_por_ciento_una_sola_vez(): void {
		Functions\when( 'get_option' )->alias(
			static fn ( string $opcion, $defecto = false ) => match ( $opcion ) {
				PresupuestoLenguaje::OPCION_LIMITE_DIARIO => 10.0,
				PresupuestoLenguaje::OPCION_GASTO => array(
					'dia'   => ( new RelojFijo() )->ahora()->format( 'Y-m-d' ),
					'gasto' => 7.0,
				),
				default => $defecto,
			}
		);
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( false );

		$llamadasATransient = array();
		Functions\when( 'set_transient' )->alias(
			static function ( string $transient, $valor, $expiracion ) use ( &$llamadasATransient ): bool {
				$llamadasATransient[] = array( $transient, $valor, $expiracion );

				return true;
			}
		);

		$llamadasAAccion = array();
		Functions\when( 'do_action' )->alias(
			static function ( string $hook, ...$argumentos ) use ( &$llamadasAAccion ): void {
				$llamadasAAccion[] = array( $hook, $argumentos );
			}
		);

		( new PresupuestoLenguaje( new RelojFijo() ) )->registrarGasto( 1.0 );

		self::assertCount( 1, $llamadasATransient );
		self::assertSame( PresupuestoLenguaje::TRANSIENT_AVISO_80, $llamadasATransient[0][0] );
		self::assertSame( 1, $llamadasATransient[0][1] );

		self::assertCount( 1, $llamadasAAccion );
		self::assertSame( 'pluma/presupuesto_al_80', $llamadasAAccion[0][0] );
		self::assertSame( 8.0, $llamadasAAccion[0][1][0] );
		self::assertSame( 10.0, $llamadasAAccion[0][1][1] );
	}

	public function test_registrar_gasto_no_repite_el_aviso_si_el_transient_ya_existe(): void {
		Functions\when( 'get_option' )->alias(
			static fn ( string $opcion, $defecto = false ) => match ( $opcion ) {
				PresupuestoLenguaje::OPCION_LIMITE_DIARIO => 10.0,
				PresupuestoLenguaje::OPCION_GASTO => array(
					'dia'   => ( new RelojFijo() )->ahora()->format( 'Y-m-d' ),
					'gasto' => 9.0,
				),
				default => $defecto,
			}
		);
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'get_transient' )->justReturn( 1 );

		$llamadasATransient = array();
		Functions\when( 'set_transient' )->alias(
			static function ( string $transient, $valor, $expiracion ) use ( &$llamadasATransient ): bool {
				$llamadasATransient[] = array( $transient, $valor, $expiracion );

				return true;
			}
		);

		$llamadasAAccion = array();
		Functions\when( 'do_action' )->alias(
			static function ( string $hook, ...$argumentos ) use ( &$llamadasAAccion ): void {
				$llamadasAAccion[] = array( $hook, $argumentos );
			}
		);

		( new PresupuestoLenguaje( new RelojFijo() ) )->registrarGasto( 0.1 );

		self::assertCount( 0, $llamadasATransient );
		self::assertCount( 0, $llamadasAAccion );
	}
}
