<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Sentimiento dominante de un comentario sustantivo (memoria de audiencia,
 * Libro Cap. 5.7).
 */
enum SentimientoAudiencia: string {

	case Positivo = 'positivo';
	case Negativo = 'negativo';
	case Mixto    = 'mixto';
	case Neutral  = 'neutral';
}
