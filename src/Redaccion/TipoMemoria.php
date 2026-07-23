<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Las tres formas de memoria editorial (Libro Cap. 5.4, Capa 3):
 * posturas defendidas, historias seguidas, y aprendizajes de audiencia.
 */
enum TipoMemoria: string {

	case Postura   = 'postura';
	case Cobertura = 'cobertura';
	case Audiencia = 'audiencia';
}
