<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Los 6 puntos de la lista de verificación del Corrector Interno
 * (Libro Cap. 5.6, pl-periodistas §Contratos innegociables 5).
 */
enum PuntoCorrector: string {

	case Hechos                   = 'hechos';
	case ProporcionInterpretativa = 'proporcion_interpretativa';
	case SolapamientoNGrama       = 'solapamiento_ngrama';
	case Voz                      = 'voz';
	case TitularHonesto           = 'titular_honesto';
	case MatrizYLineasRojas       = 'matriz_y_lineas_rojas';
}
