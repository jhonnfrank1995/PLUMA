<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use RuntimeException;

/**
 * Fallo de un proveedor de tendencias: URL insegura, HTTP fallido o feed
 * ilegible. El Sensor que lo atrapa decide degradar (Libro Cap. 3.2: "el
 * Radar de mañana añade sensores" — un proveedor caído no detiene el resto).
 */
final class ProveedorTendenciasException extends RuntimeException {

}
