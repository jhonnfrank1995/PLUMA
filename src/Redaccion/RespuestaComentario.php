<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use DateTimeImmutable;

/**
 * Un comentario real de WordPress ya procesado por la memoria de audiencia
 * (Libro Cap. 5.7, Etapa 5) — `$borrador`/`$periodistaId` nulos cuando el
 * comentario solo alimentó memoria, sin borrador de respuesta.
 */
final readonly class RespuestaComentario {

	public function __construct(
		public int $id,
		public int $piezaId,
		public int $comentarioId,
		public ?int $periodistaId,
		public ?string $borrador,
		public EstadoRespuestaComentario $estado,
		public ?int $comentarioRespuestaId,
		public DateTimeImmutable $creadaEn,
		public ?DateTimeImmutable $resueltaEn,
	) {
	}
}
