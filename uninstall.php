<?php

/**
 * Desinstalación de PLUMA Engine (GOVERNANCE §5.4, pl-wp-core §7).
 *
 * Por defecto SE CONSERVAN los datos: el banco de periodistas del cliente
 * (y, en esta Etapa, capacidades y opciones del núcleo) jamás se borra por
 * accidente al desinstalar. Solo se purga si el cliente marcó explícitamente
 * "no conservar datos" desde el panel.
 *
 * @package Pluma
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$pluma_conservar_datos = get_option( \Pluma\Kernel\Activador::OPCION_CONSERVAR_DATOS, true );

// WordPress persiste `add_option()`/`update_option()` con un booleano en la
// tabla `wp_options` como texto plano ("1" / "") y `get_option()` lo
// devuelve tal cual, NUNCA como el tipo `bool` original. Una comparación
// estricta `true !== $valor` es casi siempre verdadera contra un valor real
// de base de datos y purgaría los datos del cliente por defecto — el
// comportamiento exactamente opuesto al contrato de GOVERNANCE §5.4.
if ( ! $pluma_conservar_datos ) {
	\Pluma\Kernel\Desinstalador::purgar();
}

unset( $pluma_conservar_datos );
