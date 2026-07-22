<?php

declare(strict_types=1);

/**
 * Configuración de PHP-Scoper (GOVERNANCE §5.2, pl-wp-core §9).
 *
 * Un plugin comercial de WordPress comparte el espacio de autoload global con
 * cualquier otro plugin del sitio del cliente. Sin scoping, dos plugins que
 * dependan de versiones distintas de la misma librería colisionan en runtime
 * — la causa más común de tickets de soporte en plugins premium. Este archivo
 * solo se ejecuta en `bin/build-zip` (Etapa 6), nunca en desarrollo local.
 */

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'Pluma\\Vendor',

    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/composer\.(json|lock)/')
            ->exclude([
                'test',
                'tests',
                'Tests',
                'docs',
            ])
            ->in('vendor'),
    ],

    // El propio código del plugin (namespace Pluma\...) nunca se prefija:
    // solo las dependencias de vendor/ que se distribuyen dentro del ZIP.
    'exclude-namespaces' => [
        'Pluma',
    ],

    'exclude-constants' => [
        '/^ABSPATH$/',
        '/^WPINC$/',
    ],
];
