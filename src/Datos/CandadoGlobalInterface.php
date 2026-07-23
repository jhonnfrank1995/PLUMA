<?php

declare(strict_types=1);

namespace Pluma\Datos;

/**
 * Candado global del orquestador (CLAUDE.md § Orquestador, Libro Cap. 9.3):
 * dos ejecuciones simultáneas — la segunda sale en silencio.
 */
interface CandadoGlobalInterface {

	public function adquirir(): bool;

	public function liberar(): void;
}
