<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

/**
 * Estado de una ranura en `pluma_cola_publicacion` (Libro Cap. 9.3):
 * "expirada" cubre la perecibilidad — "mejor no publicar que publicar tarde".
 */
enum EstadoColaPublicacion: string {

	case Programada = 'programada';
	case Publicada  = 'publicada';
	case Expirada   = 'expirada';
}
