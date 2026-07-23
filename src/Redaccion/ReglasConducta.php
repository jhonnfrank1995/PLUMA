<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Reglas cualitativas de conducta (Libro Cap. 5.3): línea editorial, líneas
 * rojas personales, muletillas/firmas estilísticas (3–6 rasgos, pl-periodistas
 * §Rasgos de voz), vocabulario prohibido propio del periodista (se combina
 * con la lista global del sitio), y relación con el lector.
 */
final readonly class ReglasConducta {

	/**
	 * @param list<string> $lineasRojas
	 * @param list<string> $muletillas 3–6 rasgos verbales recurrentes
	 * @param list<string> $vocabularioProhibido
	 */
	public function __construct(
		public string $lineaEditorial,
		public array $lineasRojas,
		public array $muletillas,
		public array $vocabularioProhibido,
		public TratamientoLector $tratamientoLector,
		public string $estiloPreguntaFinal,
	) {
	}

	/**
	 * @return array{lineaEditorial: string, lineasRojas: list<string>, muletillas: list<string>, vocabularioProhibido: list<string>, tratamientoLector: string, estiloPreguntaFinal: string}
	 */
	public function aArray(): array {
		return array(
			'lineaEditorial'       => $this->lineaEditorial,
			'lineasRojas'          => $this->lineasRojas,
			'muletillas'           => $this->muletillas,
			'vocabularioProhibido' => $this->vocabularioProhibido,
			'tratamientoLector'    => $this->tratamientoLector->value,
			'estiloPreguntaFinal'  => $this->estiloPreguntaFinal,
		);
	}

	/**
	 * @param array{lineaEditorial: string, lineasRojas: list<string>, muletillas: list<string>, vocabularioProhibido: list<string>, tratamientoLector: string, estiloPreguntaFinal: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['lineaEditorial'],
			$datos['lineasRojas'],
			$datos['muletillas'],
			$datos['vocabularioProhibido'],
			TratamientoLector::from( $datos['tratamientoLector'] ),
			$datos['estiloPreguntaFinal']
		);
	}
}
