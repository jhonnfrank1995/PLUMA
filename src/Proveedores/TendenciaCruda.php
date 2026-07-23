<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use DateTimeImmutable;

/**
 * Dato sin interpretar tal como lo entrega el proveedor externo. La
 * interpretación (puntuación, deduplicación) vive en `Pluma\Sensores`.
 */
final readonly class TendenciaCruda {

	/**
	 * @param list<array{titulo: string, url: string, fuente: string}> $articulosRelacionados
	 */
	public function __construct(
		public string $termino,
		public string $traficoAproximado,
		public DateTimeImmutable $publicadaEn,
		public array $articulosRelacionados,
	) {
	}
}
