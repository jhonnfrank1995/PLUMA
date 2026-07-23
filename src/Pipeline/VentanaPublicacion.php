<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

/**
 * Franja horaria de publicación con peso (Libro Cap. 9.2): "alineadas con
 * los picos de audiencia... el sistema reparte la cuota entre ventanas".
 * `horaInicio`/`horaFin` son horas del día en `[0, 24]` (p. ej. 7 y 9 para
 * "07:00–09:00"); `horaFin` es exclusiva.
 */
final readonly class VentanaPublicacion {

	public function __construct(
		public int $horaInicio,
		public int $horaFin,
		public int $peso,
	) {
	}

	/**
	 * @return array{horaInicio: int, horaFin: int, peso: int}
	 */
	public function aArray(): array {
		return array(
			'horaInicio' => $this->horaInicio,
			'horaFin'    => $this->horaFin,
			'peso'       => $this->peso,
		);
	}

	/**
	 * @param array{horaInicio: int, horaFin: int, peso: int} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['horaInicio'], $datos['horaFin'], $datos['peso'] );
	}
}
