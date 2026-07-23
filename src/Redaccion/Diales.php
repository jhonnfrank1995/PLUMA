<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Diales de temperamento 0–100 (Libro Cap. 5.3). El Compilador de Directrices
 * los traduce a instrucciones de estilo para el Proveedor de Lenguaje.
 */
final readonly class Diales {

	private const PALABRAS_MINIMO = 600;
	private const PALABRAS_MAXIMO = 1800;

	public function __construct(
		public int $agudezaCritica,
		public int $humor,
		public int $satira,
		public int $formalidad,
		public int $vehemencia,
		public int $empatia,
		public int $densidadDatos,
		public int $longitudPreferida,
	) {
	}

	/**
	 * Interpola linealmente entre los extremos documentados en el Libro
	 * (Cap. 5.3): dial 0 → 600 palabras, dial 100 → 1.800 palabras.
	 */
	public function longitudPalabrasObjetivo(): int {
		$rango = self::PALABRAS_MAXIMO - self::PALABRAS_MINIMO;

		return (int) round( self::PALABRAS_MINIMO + ( $this->longitudPreferida / 100 ) * $rango );
	}

	/**
	 * @return array{agudezaCritica: int, humor: int, satira: int, formalidad: int, vehemencia: int, empatia: int, densidadDatos: int, longitudPreferida: int}
	 */
	public function aArray(): array {
		return array(
			'agudezaCritica'    => $this->agudezaCritica,
			'humor'             => $this->humor,
			'satira'            => $this->satira,
			'formalidad'        => $this->formalidad,
			'vehemencia'        => $this->vehemencia,
			'empatia'           => $this->empatia,
			'densidadDatos'     => $this->densidadDatos,
			'longitudPreferida' => $this->longitudPreferida,
		);
	}

	/**
	 * @param array{agudezaCritica: int, humor: int, satira: int, formalidad: int, vehemencia: int, empatia: int, densidadDatos: int, longitudPreferida: int} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['agudezaCritica'],
			$datos['humor'],
			$datos['satira'],
			$datos['formalidad'],
			$datos['vehemencia'],
			$datos['empatia'],
			$datos['densidadDatos'],
			$datos['longitudPreferida']
		);
	}
}
