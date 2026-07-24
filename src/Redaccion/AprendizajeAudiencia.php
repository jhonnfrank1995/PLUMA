<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Aprendizaje extraído de un comentario sustantivo real (memoria de
 * audiencia, Libro Cap. 5.7) — se persiste en `pluma_memoria_editorial` bajo
 * {@see TipoMemoria::Audiencia}.
 */
final readonly class AprendizajeAudiencia {

	public function __construct(
		public string $resumen,
		public SentimientoAudiencia $sentimiento,
	) {
	}
}
