<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Relación con el lector (Libro Cap. 5.3, reglas cualitativas): cómo se
 * dirige el periodista a su audiencia.
 */
enum TratamientoLector: string {

	case Tu    = 'tu';
	case Usted = 'usted';
}
