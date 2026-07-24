<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use RuntimeException;

/**
 * Fallo del proveedor de Search Console: OAuth2 fallido, HTTP fallido o
 * respuesta con formato inesperado. `Pluma\Admin\RestSearchConsole` decide
 * cómo comunicarlo — un proveedor caído no debe tumbar el motor.
 */
final class ProveedorSearchConsoleException extends RuntimeException {

}
