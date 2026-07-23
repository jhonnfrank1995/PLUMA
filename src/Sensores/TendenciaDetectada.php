<?php

declare(strict_types=1);

namespace Pluma\Sensores;

use DateTimeImmutable;

/**
 * Una tendencia detectada por un Sensor, ya puntuada (Libro Cap. 3).
 */
final readonly class TendenciaDetectada {

	/**
	 * @param list<array{titulo: string, url: string, fuente: string}> $articulosRelacionados
	 */
	public function __construct(
		public string $termino,
		public PuntuacionOportunidad $puntuacion,
		public DateTimeImmutable $detectadaEn,
		public array $articulosRelacionados,
		public string $fuenteSenal,
	) {
	}
}
