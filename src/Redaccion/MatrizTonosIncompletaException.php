<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use RuntimeException;

/**
 * Se lanza cuando la matriz de un periodista no tiene fila configurada para
 * un `TipoNoticia` que el Algoritmo de Decisión Editorial necesita consultar.
 */
final class MatrizTonosIncompletaException extends RuntimeException {
}
