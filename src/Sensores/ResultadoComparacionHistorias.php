<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Resultado de comparar una tendencia nueva contra las candidatas recientes
 * (Libro Cap. 3.4). `tendenciaRelacionadaId` solo tiene sentido cuando
 * `relacion` es {@see RelacionHistoria::Evoluciona} — identifica DE QUÉ
 * tendencia (ya cubierta) es la evolución.
 */
final readonly class ResultadoComparacionHistorias {

	public function __construct(
		public RelacionHistoria $relacion,
		public ?int $tendenciaRelacionadaId,
	) {
	}
}
