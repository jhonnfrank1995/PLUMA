<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

use RuntimeException;

/**
 * Fallo no recuperable de una compuerta: respuesta del proveedor de lenguaje
 * con formato inesperado, o un dato de entrada que la compuerta no puede
 * evaluar. Nunca se usa para "reprobar" una Pieza — eso es un diagnóstico
 * negativo normal, no una excepción.
 */
final class CompuertaException extends RuntimeException {
}
