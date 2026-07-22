<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use wpdb;

/**
 * Lee el estado real del entorno de hosting para diagnóstico y onboarding.
 *
 * Semilla del acto 1 del asistente de instalación (Libro de Arquitectura
 * §10.3: "verificación técnica del hosting y configuración del cron real") y
 * fuente de datos de la pantalla "Sala de Máquinas — Salud del sistema".
 */
final class DetectorEntorno {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	public function versionPhp(): string {
		return PHP_VERSION;
	}

	public function versionWordPress(): string {
		$version = $GLOBALS['wp_version'] ?? '';

		return is_string( $version ) ? $version : '';
	}

	public function versionBaseDatos(): string {
		$version = $this->wpdb->db_version();

		return is_string( $version ) ? $version : '';
	}

	/**
	 * `WP-Cron` solo dispara cuando alguien visita el sitio: inaceptable
	 * para un motor editorial (Libro §9.4). Se considera "cron real"
	 * configurado cuando el hosting desactivó WP-Cron explícitamente,
	 * asumiendo que un cron de servidor golpea el punto de entrada del motor.
	 */
	public function cronRealConfigurado(): bool {
		return defined( 'DISABLE_WP_CRON' ) && true === constant( 'DISABLE_WP_CRON' );
	}

	public function esMultisitio(): bool {
		return is_multisite();
	}
}
