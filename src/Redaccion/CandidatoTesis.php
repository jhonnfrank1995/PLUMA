<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Un candidato de tesis del Paso 3 del Algoritmo de Decisión Editorial
 * (Libro Cap. 5.5), puntuado por originalidad, compatibilidad con la línea
 * editorial, sustento en hechos verificados, y potencial de conversación.
 */
final readonly class CandidatoTesis {

	public function __construct(
		public string $tesis,
		public float $puntuacionOriginalidad,
		public float $puntuacionCompatibilidadLinea,
		public float $puntuacionSustento,
		public float $puntuacionConversacional,
	) {
	}

	/**
	 * Media simple de las cuatro puntuaciones (Libro Cap. 5.5): una tesis sin
	 * sustento en hechos verificados debe descartarse antes de llegar aquí,
	 * no diluirse en el promedio — ver `SelectorAngulo::candidatosValidos()`.
	 */
	public function puntuacionTotal(): float {
		return (
			$this->puntuacionOriginalidad
			+ $this->puntuacionCompatibilidadLinea
			+ $this->puntuacionSustento
			+ $this->puntuacionConversacional
		) / 4.0;
	}

	/**
	 * @return array{tesis: string, puntuacionOriginalidad: float, puntuacionCompatibilidadLinea: float, puntuacionSustento: float, puntuacionConversacional: float}
	 */
	public function aArray(): array {
		return array(
			'tesis'                         => $this->tesis,
			'puntuacionOriginalidad'        => $this->puntuacionOriginalidad,
			'puntuacionCompatibilidadLinea' => $this->puntuacionCompatibilidadLinea,
			'puntuacionSustento'            => $this->puntuacionSustento,
			'puntuacionConversacional'      => $this->puntuacionConversacional,
		);
	}

	/**
	 * @param array{tesis: string, puntuacionOriginalidad: float, puntuacionCompatibilidadLinea: float, puntuacionSustento: float, puntuacionConversacional: float} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['tesis'],
			$datos['puntuacionOriginalidad'],
			$datos['puntuacionCompatibilidadLinea'],
			$datos['puntuacionSustento'],
			$datos['puntuacionConversacional']
		);
	}
}
