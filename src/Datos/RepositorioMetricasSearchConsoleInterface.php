<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Proveedores\FilaAnaliticaSearchConsole;

/**
 * Contrato del repositorio de métricas de Search Console (Libro Cap. 6.4).
 */
interface RepositorioMetricasSearchConsoleInterface {

	/**
	 * Persiste un lote real de `searchAnalytics.query`, resolviendo cada
	 * página a su Pieza (si la hay) — upsert por (post_id, consulta): una
	 * sincronización repetida sobre el mismo rango de fechas actualiza la
	 * fila existente en vez de duplicarla.
	 *
	 * @param list<FilaAnaliticaSearchConsole> $filas
	 *
	 * @return int filas guardadas (creadas o actualizadas)
	 */
	public function guardarLote( array $filas, DateTimeImmutable $ahora ): int;

	/**
	 * Métricas más recientes para el panel (Sala de Máquinas), sin importar
	 * si la página mapeó a una Pieza o no.
	 *
	 * @return list<array{postId: int, piezaId: ?int, consulta: string, clics: int, impresiones: int, ctr: float, posicion: float, sincronizadaEn: string}>
	 */
	public function obtenerRecientes( int $limite ): array;
}
