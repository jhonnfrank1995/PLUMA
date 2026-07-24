<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Estado de un comentario real ya procesado por la memoria de audiencia
 * (Libro Cap. 5.7, Etapa 5).
 */
enum EstadoRespuestaComentario: string {

	/** Solo alimentó memoria de audiencia — sin periodista o sin respuestas habilitadas. */
	case Procesado = 'procesado';
	/** Hay un borrador de respuesta esperando aprobación del editor. */
	case PendienteAprobacion = 'pendiente_aprobacion';
	/** El editor aprobó el borrador; ya se publicó como comentario real. */
	case Aprobado = 'aprobado';
	/** El editor descartó el borrador. */
	case Descartado = 'descartado';
}
