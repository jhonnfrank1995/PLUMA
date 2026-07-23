<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\Pieza;
use Pluma\Redaccion\FichaDecisionEditorial;

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

	/**
	 * Paso 2 del Algoritmo de Decisión Editorial (Libro Cap. 5.5): registra
	 * qué periodista y qué versión de su Conducta redactó la pieza.
	 */
	public function asignarPeriodista( int $id, int $periodistaId, int $periodistaVersionId, DateTimeImmutable $ahora ): bool;

	public function actualizarFichaDecisionEditorial( int $id, FichaDecisionEditorial $ficha, DateTimeImmutable $ahora ): bool;

	/**
	 * Piezas asignadas a `$periodistaId` desde `$desde` (inclusive). Paso 2
	 * del Algoritmo de Decisión Editorial (Libro Cap. 5.5): "balance de
	 * carga — nadie firma 10 piezas seguidas el mismo día".
	 */
	public function contarAsignadasDesde( int $periodistaId, DateTimeImmutable $desde ): int;
}
