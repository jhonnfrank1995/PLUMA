<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use Pluma\Datos\Migrador;
use wpdb;

/**
 * Ciclo de vida — activación (pl-wp-core §7).
 *
 * Instala capacidades, sube el esquema a la versión objetivo y dispara la
 * opción de conservación de datos con su valor por defecto (conservar).
 * Soporta activación de red completa en multisitio.
 */
final class Activador {

	public const OPCION_CONSERVAR_DATOS = 'pluma_conservar_datos_al_desinstalar';
	public const OPCION_ACTIVADO_EN     = 'pluma_activado_en';

	public static function activarParaRed( bool $redCompleta, RelojInterface $reloj, string $versionEsquemaObjetivo ): void {
		if ( is_multisite() && $redCompleta ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $idSitio ) {
				switch_to_blog( (int) $idSitio );
				self::activar( $reloj, $versionEsquemaObjetivo );
				restore_current_blog();
			}

			return;
		}

		self::activar( $reloj, $versionEsquemaObjetivo );
	}

	public static function activar( RelojInterface $reloj, string $versionEsquemaObjetivo ): void {
		global $wpdb;
		assert( $wpdb instanceof wpdb );

		Capacidades::instalar();
		( new Migrador( $wpdb ) )->migrar( $versionEsquemaObjetivo );

		// `add_option` no sobrescribe un valor ya existente: reactivar el
		// plugin nunca resetea la elección de conservación del cliente.
		add_option( self::OPCION_CONSERVAR_DATOS, true, '', false );
		update_option( self::OPCION_ACTIVADO_EN, $reloj->ahora()->format( DATE_ATOM ), false );
	}
}
