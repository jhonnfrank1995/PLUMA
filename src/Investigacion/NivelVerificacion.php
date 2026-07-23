<?php

declare(strict_types=1);

namespace Pluma\Investigacion;

/**
 * Nivel de confianza de un hecho del expediente (Libro Cap. 4.2 — triangulación).
 */
enum NivelVerificacion: string {

	case Verificado = 'verificado';
	case Atribuido  = 'atribuido';
	case Disputado  = 'disputado';
}
