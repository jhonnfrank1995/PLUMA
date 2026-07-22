<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Brain\Monkey\Functions;
use Pluma\Kernel\DetectorEntorno;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use wpdb;

/**
 * @covers \Pluma\Kernel\DetectorEntorno
 */
final class DetectorEntornoTest extends CasoDePruebaUnitario {

	protected function tearDown(): void {
		unset( $GLOBALS['wp_version'] );
		parent::tearDown();
	}

	public function test_version_php_devuelve_la_constante_real_del_runtime(): void {
		$detector = new DetectorEntorno( new wpdb() );

		self::assertSame( PHP_VERSION, $detector->versionPhp() );
	}

	public function test_version_wordpress_lee_la_global_wp_version(): void {
		$GLOBALS['wp_version'] = '6.7.1';

		$detector = new DetectorEntorno( new wpdb() );

		self::assertSame( '6.7.1', $detector->versionWordPress() );
	}

	public function test_version_base_datos_delega_en_wpdb(): void {
		$detector = new DetectorEntorno( new wpdb() );

		self::assertSame( '8.0.36', $detector->versionBaseDatos() );
	}

	public function test_cron_real_configurado_es_falso_si_disable_wp_cron_no_esta_definida(): void {
		$detector = new DetectorEntorno( new wpdb() );

		self::assertFalse( $detector->cronRealConfigurado() );
	}

	public function test_es_multisitio_delega_en_is_multisite(): void {
		Functions\expect( 'is_multisite' )->once()->andReturn( true );

		$detector = new DetectorEntorno( new wpdb() );

		self::assertTrue( $detector->esMultisitio() );
	}
}
