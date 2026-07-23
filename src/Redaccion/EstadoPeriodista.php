<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Estado del periodista en el banco (Libro Cap. 5.8): un periodista jubilado
 * conserva su identidad, memoria e historial (página de autor, atribución de
 * piezas pasadas), pero deja de ser candidato en la asignación (Paso 2 del
 * Algoritmo de Decisión Editorial).
 */
enum EstadoPeriodista: string {

	case Activo   = 'activo';
	case Jubilado = 'jubilado';
}
