<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Tonos dominante/de apoyo de la matriz (Libro Cap. 5.3).
 */
enum Tono: string {

	case Analitico           = 'analitico';
	case Critico             = 'critico';
	case InformativoEmpatico = 'informativo_empatico';
	case Humoristico         = 'humoristico';
	case Opinion             = 'opinion';
	case Persuasivo          = 'persuasivo';
}
