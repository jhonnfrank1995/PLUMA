<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use DateTimeImmutable;

/**
 * Una entrada de la Memoria editorial de un periodista (Libro Cap. 5.4):
 * postura defendida, historia seguida, o aprendizaje de audiencia, según
 * `$tipo`. `$contenido` tiene forma distinta por tipo — ver
 * `RepositorioMemoriaEditorial` para el contrato exacto de cada una.
 */
final readonly class EntradaMemoria {

	/**
	 * @param array<string, mixed> $contenido
	 */
	public function __construct(
		public int $id,
		public int $periodistaId,
		public TipoMemoria $tipo,
		public string $tema,
		public array $contenido,
		public ?int $piezaId,
		public DateTimeImmutable $creadaEn,
	) {
	}
}
