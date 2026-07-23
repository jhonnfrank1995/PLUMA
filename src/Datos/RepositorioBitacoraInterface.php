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
}
