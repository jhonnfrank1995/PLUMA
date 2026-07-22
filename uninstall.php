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

if ( true !== $pluma_conservar_datos ) {
	\Pluma\Kernel\Desinstalador::purgar();
}

unset( $pluma_conservar_datos );
