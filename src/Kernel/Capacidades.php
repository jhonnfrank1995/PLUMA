<?php

declare(strict_types=1);

namespace Pluma\Kernel;

/**
 * Capacidades propias de PLUMA (GOVERNANCE §1.4, CLAUDE.md § Estándares WordPress).
 *
 * Nunca se cuelga funcionalidad de `manage_options`: cada acción del panel
 * exige una capacidad nombrada que un administrador puede conceder a un rol
 * distinto (p. ej. un "editor de revisión" que aprueba piezas sin poder
 * tocar la configuración del motor).
 */
final class Capacidades {

	public const GESTIONAR_PERIODISTAS = 'pluma_gestionar_periodistas';
	public const APROBAR_PIEZAS        = 'pluma_aprobar_piezas';
	public const CONFIGURAR_MOTOR      = 'pluma_configurar_motor';

	/**
	 * @return list<string>
	 */
	public static function todas(): array {
		return array(
			self::GESTIONAR_PERIODISTAS,
			self::APROBAR_PIEZAS,
			self::CONFIGURAR_MOTOR,
		);
	}

	/**
	 * Concede las tres capacidades al rol `administrator`. Se ejecuta en
	 * activación y es idempotente: `add_cap` sobre una capacidad ya
	 * concedida no produce efecto adicional.
	 */
	public static function instalar(): void {
		$administrador = get_role( 'administrator' );

		if ( null === $administrador ) {
			return;
		}

		foreach ( self::todas() as $capacidad ) {
			$administrador->add_cap( $capacidad );
		}
	}

	/**
	 * Revoca las capacidades de todos los roles que las tengan. Solo se
	 * invoca desde `uninstall.php` cuando el cliente elige NO conservar
	 * datos al desinstalar.
	 */
	public static function desinstalar(): void {
		global $wp_roles;

		if ( ! isset( $wp_roles ) || ! is_object( $wp_roles ) || ! property_exists( $wp_roles, 'role_objects' ) ) {
			return;
		}

		foreach ( $wp_roles->role_objects as $rol ) {
			foreach ( self::todas() as $capacidad ) {
				$rol->remove_cap( $capacidad );
			}
		}
	}
}
