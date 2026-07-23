<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Temas legalmente regulados (Libro Cap. 8.2): reciben descargos o
 * degradación según la jurisdicción configurada del sitio.
 */
enum TemaRegulado: string {

	case Salud      = 'salud';
	case Financiero = 'financiero';
	case Legal      = 'legal';
}
