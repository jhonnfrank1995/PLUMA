<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Relación entre una tendencia nueva y una ya procesada (Libro Cap. 3.4):
 * ¿es la misma historia bajo otro titular, la misma historia que evoluciona
 * ("dos golpes"), o no tienen relación?
 */
enum RelacionHistoria: string {

	case Identica    = 'identica';
	case Evoluciona  = 'evoluciona';
	case SinRelacion = 'sin_relacion';
}
