<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Redaccion\AnotacionCorrector;
use Pluma\Redaccion\Borrador;

/**
 * Contrato del repositorio de Borradores (Libro Cap. 5.6, Cap. 11): el
 * historial de revisión de una Pieza, con las anotaciones del Corrector
 * Interno en cada ciclo.
 */
interface RepositorioBorradoresInterface {

	/**
	 * @param list<AnotacionCorrector> $anotaciones
	 */
	public function crear(
		int $piezaId,
		int $numeroCiclo,
		string $contenido,
		array $anotaciones,
		bool $aprobadoPorCorrector,
		DateTimeImmutable $ahora,
		bool $editadoManualmente = false
	): int;

	/**
	 * @return list<Borrador> ordenados por número de ciclo ascendente
	 */
	public function obtenerPorPieza( int $piezaId ): array;

	public function obtenerUltimo( int $piezaId ): ?Borrador;
}
