<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\Pieza;

/**
 * Contrato del repositorio de Piezas. `Pluma\Pipeline\Transicionador` y
 * `Orquestador` dependen de esta interfaz, jamás de la implementación
 * concreta sobre `$wpdb` — así son testeables en Unit con un doble simple.
 */
interface RepositorioPiezasInterface {

	public function crear( int $tendenciaId, DateTimeImmutable $ahora ): int;

	public function obtenerPorId( int $id ): ?Pieza;

	/**
	 * @return list<Pieza>
	 */
	public function obtenerPorEstado( EstadoPieza $estado, int $limite ): array;

	/**
	 * Actualización optimista: solo aplica si la fila sigue en
	 * `$estadoEsperado` (candado por-Pieza — pl-pipeline §2). Devuelve
	 * `false` si otra ejecución ya la movió.
	 */
	public function actualizarEstado(
		int $id,
		EstadoPieza $estadoEsperado,
		EstadoPieza $nuevoEstado,
		DateTimeImmutable $ahora
	): bool;

	public function actualizarExpediente( int $id, Expediente $expediente, DateTimeImmutable $ahora ): bool;

	public function actualizarPostId( int $id, int $postId, DateTimeImmutable $ahora ): bool;
}
