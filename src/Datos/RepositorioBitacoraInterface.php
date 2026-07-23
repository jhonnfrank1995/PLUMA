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
}
