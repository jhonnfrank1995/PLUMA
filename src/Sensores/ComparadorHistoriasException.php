<?php

declare(strict_types=1);

namespace Pluma\Sensores;

use RuntimeException;

/**
 * Fallo interno de interpretación de la respuesta del proveedor de lenguaje
 * al comparar historias — nunca escapa de {@see ComparadorHistorias::comparar()},
 * que la captura como parte de su fail-safe (Libro Cap. 3.4: un fallo aquí
 * nunca debe bloquear el tick del Orquestador).
 */
final class ComparadorHistoriasException extends RuntimeException {

}
