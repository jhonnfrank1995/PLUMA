<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Sensores\TendenciaDetectada;

interface RepositorioTendenciasInterface {

	/**
	 * Deduplicación exacta por término normalizado + fuente de señal
	 * (huella semántica real queda como deuda — Libro Cap. 3.4).
	 */
	public function existePorTermino( string $termino, string $fuenteSenal ): bool;

	public function guardar( TendenciaDetectada $tendencia, DateTimeImmutable $ahora ): int;

	/**
	 * @return array{termino: string, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>}|null
	 */
	public function obtenerPorId( int $id ): ?array;

	/**
	 * Tendencias más recientes ordenadas por puntuación total (Portada,
	 * Libro Cap. 10.2: "tendencias calientes ahora"). Sin acciones ni
	 * estado propio todavía — eso llega con la Sala de Tendencias.
	 *
	 * @return list<array{id: int, termino: string, puntuacionTotal: float, detectadaEn: string}>
	 */
	public function obtenerRecientes( int $limite ): array;
}
