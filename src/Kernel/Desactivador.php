<?php

declare(strict_types=1);

namespace Pluma\Kernel;

/**
 * Ciclo de vida — desactivación (pl-wp-core §7).
 *
 * Pausa el motor sin borrar nada: limpia únicamente los avisos transitorios
 * del panel (que deben re-evaluarse en la próxima activación) y dejar
 * constancia del momento de desactivación para el modo diagnóstico de
 * soporte (GOVERNANCE §5.6). Ninguna opción de datos del cliente se toca.
 */
final class Desactivador {

	public const OPCION_DESACTIVADO_EN = 'pluma_desactivado_en';
	public const AVISO_CRON_TRANSIENT  = 'pluma_aviso_cron';

	public static function desactivar( RelojInterface $reloj ): void {
		delete_transient( self::AVISO_CRON_TRANSIENT );
		update_option( self::OPCION_DESACTIVADO_EN, $reloj->ahora()->format( DATE_ATOM ), false );
	}
}
