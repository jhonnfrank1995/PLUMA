<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Los tres modos de operación (Libro Cap. 2.4): seleccionables globalmente,
 * degradables automáticamente por sensibilidad (nunca al revés — ninguna
 * configuración puede escalar de Piloto a Autónomo sin acción humana).
 */
enum ModoOperacion: string {

	case Piloto   = 'piloto';
	case Copiloto = 'copiloto';
	case Autonomo = 'autonomo';
}
