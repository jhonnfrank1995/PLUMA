<?php

/**
 * Bootstrap exclusivo de PHPStan: declara las constantes que `pluma-engine.php`
 * define en runtime real, para que el análisis estático las reconozca en
 * cualquier archivo de `src/` sin ejecutar WordPress.
 *
 * @package Pluma
 */

declare(strict_types=1);

if ( ! defined( 'PLUMA_ENGINE_VERSION' ) ) {
	define( 'PLUMA_ENGINE_VERSION', '0.9.0' );
}

if ( ! defined( 'PLUMA_ENGINE_DB_VERSION_OBJETIVO' ) ) {
	define( 'PLUMA_ENGINE_DB_VERSION_OBJETIVO', '0.9.0' );
}

if ( ! defined( 'PLUMA_ENGINE_ARCHIVO' ) ) {
	define( 'PLUMA_ENGINE_ARCHIVO', __DIR__ . '/../pluma-engine.php' );
}

if ( ! defined( 'PLUMA_ENGINE_DIR' ) ) {
	define( 'PLUMA_ENGINE_DIR', __DIR__ . '/../' );
}

if ( ! defined( 'PLUMA_ENGINE_URL' ) ) {
	define( 'PLUMA_ENGINE_URL', 'https://ejemplo.test/wp-content/plugins/pluma-engine/' );
}
