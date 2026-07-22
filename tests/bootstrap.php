<?php

/**
 * Bootstrap de la suite Unit (Brain\Monkey, sin WordPress cargado).
 *
 * @package Pluma
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! class_exists( 'wpdb' ) ) {
	/**
	 * Doble mínimo de `wpdb` para la suite Unit: WordPress no está cargado
	 * (Brain\Monkey solo simula funciones sueltas), pero `Pluma\Datos\Migrador`
	 * y `Pluma\Kernel\DetectorEntorno` tipan su constructor contra la clase
	 * real. Este doble expone solo la forma que ambos consumen; ninguna
	 * consulta real se ejecuta jamás a través de él.
	 */
	// phpcs:ignore PEAR.NamingConventions.ValidClassName.StartWithCapital -- debe llamarse exactamente como la clase real de WordPress.
	class wpdb {

		public string $prefix = 'wp_';

		public function db_version(): string {
			return '8.0.36';
		}
	}
}
