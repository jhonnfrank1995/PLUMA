<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use RuntimeException;

/**
 * Fallo del Algoritmo de Decisión Editorial (Libro Cap. 5.5): respuesta del
 * proveedor de lenguaje con formato inesperado, o banco de periodistas sin
 * candidatos elegibles.
 */
final class DecisionEditorialException extends RuntimeException {
}
