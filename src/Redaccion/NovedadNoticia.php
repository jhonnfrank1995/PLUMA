<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Eje "novedad" del Paso 1 del Algoritmo de Decisión Editorial (Libro Cap. 5.5).
 */
enum NovedadNoticia: string {

	case Primicia            = 'primicia';
	case HistoriaEnEvolucion = 'historia_en_evolucion';
}
