<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Diagnóstico de la Compuerta de Originalidad (Libro Cap. 8.3): "si la pieza
 * no añade, no sale" — la defensa anti scaled-content-abuse del producto.
 */
final readonly class DiagnosticoOriginalidad {

	public function __construct(
		public bool $solapamientoConFuentes,
		public bool $solapamientoConSitioPropio,
		public float $ratioGananciaInformacion,
		public float $umbralGananciaMinima,
	) {
	}

	public function aprobada(): bool {
		return ! $this->solapamientoConFuentes
			&& ! $this->solapamientoConSitioPropio
			&& $this->ratioGananciaInformacion >= $this->umbralGananciaMinima;
	}

	/**
	 * @return array{solapamientoConFuentes: bool, solapamientoConSitioPropio: bool, ratioGananciaInformacion: float, umbralGananciaMinima: float}
	 */
	public function aArray(): array {
		return array(
			'solapamientoConFuentes'     => $this->solapamientoConFuentes,
			'solapamientoConSitioPropio' => $this->solapamientoConSitioPropio,
			'ratioGananciaInformacion'   => $this->ratioGananciaInformacion,
			'umbralGananciaMinima'       => $this->umbralGananciaMinima,
		);
	}

	/**
	 * @param array{solapamientoConFuentes: bool, solapamientoConSitioPropio: bool, ratioGananciaInformacion: float, umbralGananciaMinima: float} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['solapamientoConFuentes'],
			$datos['solapamientoConSitioPropio'],
			$datos['ratioGananciaInformacion'],
			$datos['umbralGananciaMinima']
		);
	}
}
