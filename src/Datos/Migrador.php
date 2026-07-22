<?php

declare(strict_types=1);

namespace Pluma\Datos;

use wpdb;

/**
 * Migrador de esquema de PLUMA (sub-agente ESQUEMA, GOVERNANCE §1.4, pl-wp-core §3).
 *
 * Único punto del plugin que invoca `dbDelta`. La Etapa 0 no define tablas
 * todavía — la Pieza y su máquina de estados nacen en la Etapa 1 — pero la
 * infraestructura de versión y migración debe existir desde el primer commit
 * para que ninguna tabla futura se cree jamás fuera de este contrato.
 */
final class Migrador {

	public const OPCION_VERSION = 'pluma_db_version';

	public function __construct( private readonly wpdb $wpdb ) {
	}

	public function versionInstalada(): string {
		$version = get_option( self::OPCION_VERSION, '0.0.0' );

		return is_string( $version ) ? $version : '0.0.0';
	}

	/**
	 * Ejecuta las sentencias `CREATE TABLE` (formato dbDelta) necesarias
	 * para alcanzar `$versionObjetivo` y deja la versión registrada.
	 *
	 * Idempotente por construcción: `dbDelta` no reaplica cambios ya
	 * presentes y `update_option` con el mismo valor no escribe de más.
	 *
	 * @param list<string> $sentenciasCreateTable sentencias en formato dbDelta, una tabla por elemento
	 */
	public function migrar( string $versionObjetivo, array $sentenciasCreateTable = array() ): void {
		if ( array() !== $sentenciasCreateTable ) {
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			foreach ( $sentenciasCreateTable as $sentencia ) {
				dbDelta( $sentencia );
			}
		}

		if ( $this->versionInstalada() !== $versionObjetivo ) {
			update_option( self::OPCION_VERSION, $versionObjetivo, false );
		}
	}

	public function prefijoTablas(): string {
		return $this->wpdb->prefix . 'pluma_';
	}
}
