<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Sensores\EstadoTendencia;
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
	 * Libro Cap. 10.2: "tendencias calientes ahora").
	 *
	 * @return list<array{id: int, termino: string, puntuacionTotal: float, detectadaEn: string}>
	 */
	public function obtenerRecientes( int $limite ): array;

	/**
	 * Tarjetas completas para la Sala de Tendencias (Libro Cap. 10.2):
	 * puntuación desglosada, estado y quién la está cubriendo ya. Excluye
	 * las IGNORADAS — el editor las sacó de la agenda.
	 *
	 * @return list<array{id: int, termino: string, fuenteSenal: string, velocidad: float, afinidad: float, puntuacionTotal: float, estado: EstadoTendencia, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>, detectadaEn: string}>
	 */
	public function obtenerParaSala( int $limite ): array;

	/**
	 * Cambia el estado de la tendencia en el radar (Sala de Tendencias:
	 * Cubrir ahora / Ignorar / Vigilar). Devuelve `false` si no existe.
	 */
	public function actualizarEstadoTendencia( int $id, EstadoTendencia $estado ): bool;
}
