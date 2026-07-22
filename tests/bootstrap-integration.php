<?php

/**
 * Bootstrap de la suite Integración (wp-env + suite de tests de WordPress).
 *
 * Patrón oficial del WordPress Developer Handbook para PHPUnit contra un
 * core real: localiza la suite de tests de WP (que `wp-env` descarga y
 * expone vía `WP_TESTS_DIR` dentro del contenedor `tests-cli`), registra la
 * carga de PLUMA como si fuera un plugin activo, y arranca el core de tests.
 *
 * @package Pluma
 */

declare(strict_types=1);

$pluma_tests_dir = getenv('WP_TESTS_DIR');

if (false === $pluma_tests_dir || '' === $pluma_tests_dir) {
    $pluma_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $pluma_tests_dir . '/includes/functions.php';

/**
 * Carga PLUMA Engine como si WordPress lo hubiera activado.
 */
function _pluma_cargar_plugin_bajo_prueba(): void
{
    require dirname(__DIR__) . '/pluma-engine.php';
}
tests_add_filter('muplugins_loaded', '_pluma_cargar_plugin_bajo_prueba');

require $pluma_tests_dir . '/includes/bootstrap.php';
