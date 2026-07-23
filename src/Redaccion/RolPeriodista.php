<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Rol en la redacción (Libro Cap. 5.2, Capa 1 — Identidad).
 */
enum RolPeriodista: string {

	case Analista   = 'analista';
	case Columnista = 'columnista';
	case Cronista   = 'cronista';
	case Satirico   = 'satirico';
}
