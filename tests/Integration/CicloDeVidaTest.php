<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Datos\Migrador;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Desactivador;
use Pluma\Kernel\Desinstalador;
use Pluma\Kernel\RelojSistema;
use Pluma\Tests\Unit\Dobles\RelojFijo;
use WP_UnitTestCase;

/**
 * Ciclo de vida contra un WordPress real (wp-env): activación, desactivación
 * y desinstalación con capacidades, opciones y roles verdaderos.
 *
 * @covers \Pluma\Kernel\Activador
 * @covers \Pluma\Kernel\Desactivador
 * @covers \Pluma\Kernel\Desinstalador
 */
final class CicloDeVidaTest extends WP_UnitTestCase {

	public function test_activar_concede_las_tres_capacidades_al_rol_administrador_real(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );

		$administrador = get_role( 'administrator' );

		self::assertNotNull( $administrador );
		self::assertTrue( $administrador->has_cap( 'pluma_gestionar_periodistas' ) );
		self::assertTrue( $administrador->has_cap( 'pluma_aprobar_piezas' ) );
		self::assertTrue( $administrador->has_cap( 'pluma_configurar_motor' ) );
	}

	public function test_activar_deja_la_version_de_esquema_registrada(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );

		self::assertSame( '0.1.0', get_option( Migrador::OPCION_VERSION ) );
	}

	public function test_activar_conserva_datos_por_defecto(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );

		self::assertEquals( true, get_option( Activador::OPCION_CONSERVAR_DATOS ) );
	}

	public function test_activar_dos_veces_es_idempotente(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );
		$primeraVersion = get_option( Migrador::OPCION_VERSION );

		Activador::activar( new RelojSistema(), '0.1.0' );
		$segundaVersion = get_option( Migrador::OPCION_VERSION );

		self::assertSame( $primeraVersion, $segundaVersion );
		self::assertTrue( get_role( 'administrator' )->has_cap( 'pluma_configurar_motor' ) );
	}

	public function test_desactivar_no_borra_ninguna_opcion_de_datos_del_cliente(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );

		Desactivador::desactivar( new RelojSistema() );

		self::assertSame( '0.1.0', get_option( Migrador::OPCION_VERSION ) );
		self::assertEquals( true, get_option( Activador::OPCION_CONSERVAR_DATOS ) );
		self::assertTrue( get_role( 'administrator' )->has_cap( 'pluma_configurar_motor' ) );
	}

	public function test_purgar_revoca_capacidades_y_borra_las_opciones_del_nucleo(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );

		Desinstalador::purgar();

		self::assertFalse( get_role( 'administrator' )->has_cap( 'pluma_configurar_motor' ) );
		self::assertFalse( get_option( Migrador::OPCION_VERSION ) );
		self::assertFalse( get_option( Activador::OPCION_CONSERVAR_DATOS ) );
	}

	/**
	 * Regresión: WordPress persiste `add_option()`/`update_option()` con un
	 * booleano como texto plano ("1"/"") en `wp_options`, y `get_option()` lo
	 * devuelve tal cual — nunca como `bool`. `uninstall.php` comparaba
	 * `true !== $valor`, que es casi siempre verdadero contra un valor real
	 * de base de datos y purgaba los datos del cliente por defecto (detectado
	 * en CI: smoke de instalación desde el ZIP, run 29968141940).
	 */
	public function test_uninstall_conserva_datos_cuando_wp_devuelve_el_valor_como_string(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );
		update_option( Activador::OPCION_CONSERVAR_DATOS, '1' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		self::assertSame( '0.1.0', get_option( Migrador::OPCION_VERSION ) );
		self::assertTrue( get_role( 'administrator' )->has_cap( 'pluma_configurar_motor' ) );
	}

	public function test_uninstall_purga_cuando_el_cliente_eligio_no_conservar(): void {
		Activador::activar( new RelojSistema(), '0.1.0' );
		update_option( Activador::OPCION_CONSERVAR_DATOS, '' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		self::assertFalse( get_option( Migrador::OPCION_VERSION ) );
		self::assertFalse( get_role( 'administrator' )->has_cap( 'pluma_configurar_motor' ) );
	}

	/**
	 * Regresión real (encontrada probando manualmente en `wp-env` durante la
	 * Etapa 4, porción 7): una actualización normal de WordPress reemplaza
	 * los archivos del plugin SIN disparar `register_activation_hook` — el
	 * esquema instalado se quedaba congelado en la última versión activada a
	 * mano mientras el código corría migraciones más nuevas, produciendo
	 * errores reales de SQL por columnas/tablas faltantes.
	 * `Activador::actualizarEsquemaSiHaceFalta()` (invocado en cada
	 * `plugins_loaded`) cierra ese hueco.
	 */
	public function test_actualizar_esquema_si_hace_falta_migra_cuando_la_version_esta_desactualizada(): void {
		Activador::activar( new RelojFijo( '2026-01-01T00:00:00+00:00' ), '0.1.0' );

		Activador::actualizarEsquemaSiHaceFalta( new RelojFijo( '2030-01-01T00:00:00+00:00' ), '0.2.0' );

		self::assertSame( '0.2.0', get_option( Migrador::OPCION_VERSION ) );
		self::assertSame( '2030-01-01T00:00:00+00:00', get_option( Activador::OPCION_ACTIVADO_EN ) );
	}

	public function test_actualizar_esquema_si_hace_falta_no_toca_nada_si_ya_esta_al_dia(): void {
		Activador::activar( new RelojFijo( '2026-01-01T00:00:00+00:00' ), '0.1.0' );

		Activador::actualizarEsquemaSiHaceFalta( new RelojFijo( '2030-01-01T00:00:00+00:00' ), '0.1.0' );

		self::assertSame( '0.1.0', get_option( Migrador::OPCION_VERSION ) );
		self::assertSame( '2026-01-01T00:00:00+00:00', get_option( Activador::OPCION_ACTIVADO_EN ) );
	}
}
