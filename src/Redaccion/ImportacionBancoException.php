<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use RuntimeException;

/**
 * El archivo de importación del banco de periodistas no tiene el formato
 * esperado (pl-periodistas §8: "todo cambio de esquema mantiene
 * compatibilidad de import o migra explícitamente").
 */
final class ImportacionBancoException extends RuntimeException {
}
