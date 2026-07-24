<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;

interface RepositorioBitacoraInterface {

	public function iniciarEjecucion( DateTimeImmutable $ahora ): int;

	/**
	 * @param list<string> $errores
	 */
	public function finalizarEjecucion( int $id, DateTimeImmutable $ahora, int $lotesProcesados, array $errores ): void;

	/**
	 * La ejecución más reciente del motor (Portada, Libro Cap. 10.1: "salud
	 * del motor — última ejecución"). `null` si el motor nunca corrió.
	 *
	 * @return array{iniciadaEn: string, finalizadaEn: ?string, lotesProcesados: int, errores: list<string>}|null
	 */
	public function obtenerUltima(): ?array;

	/**
	 * Las últimas `$limite` ejecuciones, más reciente primero (Sala de
	 * Máquinas, Libro Cap. 10.2: "la bitácora del motor — ejecuciones,
	 * duración, piezas avanzadas, errores").
	 *
	 * @return list<array{iniciadaEn: string, finalizadaEn: ?string, lotesProcesados: int, errores: list<string>}>
	 */
	public function obtenerRecientes( int $limite ): array;

	/**
	 * Ejecuciones iniciadas dentro de `[$desde, $hasta]` (Libro Cap. 14,
	 * Etapa 5: informes editoriales semanales) — sin el límite de filas de
	 * `obtenerRecientes()`, acota por fecha en vez de por cantidad.
	 *
	 * @return list<array{iniciadaEn: string, finalizadaEn: ?string, lotesProcesados: int, errores: list<string>}>
	 */
	public function obtenerEntre( DateTimeImmutable $desde, DateTimeImmutable $hasta ): array;
}
