<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Redaccion\RespuestaComentario;

/**
 * Contrato del repositorio de comentarios procesados (Libro Cap. 5.7,
 * Etapa 5: memoria de audiencia + respuestas asistidas).
 */
interface RepositorioRespuestasComentariosInterface {

	/**
	 * Un comentario real se procesa como mucho una vez (`comentario_id` es
	 * `UNIQUE`) — evita reanalizarlo/re-ofrecer un borrador en cada tick.
	 */
	public function yaProcesado( int $comentarioId ): bool;

	public function registrar(
		int $piezaId,
		int $comentarioId,
		?int $periodistaId,
		?string $borrador,
		EstadoRespuestaComentario $estado,
		DateTimeImmutable $ahora
	): int;

	public function obtenerPorId( int $id ): ?RespuestaComentario;

	/**
	 * @return list<RespuestaComentario>
	 */
	public function obtenerPendientes( int $limite ): array;

	public function contarPendientes(): int;

	public function marcarResuelta(
		int $id,
		EstadoRespuestaComentario $nuevoEstado,
		?int $comentarioRespuestaId,
		DateTimeImmutable $ahora
	): bool;
}
