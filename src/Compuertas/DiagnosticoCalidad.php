<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Diagnóstico de la Compuerta de Calidad (Libro Cap. 8.1): puntuación
 * compuesta 0–100. Por debajo del umbral configurado, RETENIDA con este
 * diagnóstico como motivo.
 *
 * `sustentoAprobado` es un veto duro, no un factor de puntuación más
 * (GOVERNANCE §2.4: "toda afirmación del borrador debe ser trazable a un
 * hecho del expediente"): una pieza sin sustento reprueba Calidad sin
 * importar cuántos puntos sume en los demás ejes. Diluir la falta de
 * sustento en un promedio permitiría que una alucinación "se compre" con
 * buena legibilidad — exactamente lo que la regla de oro prohíbe.
 */
final readonly class DiagnosticoCalidad {

	/**
	 * @param list<string> $detalle
	 */
	public function __construct(
		public int $puntuacionTotal,
		public int $umbral,
		public bool $sustentoAprobado,
		public array $detalle,
	) {
	}

	public function aprobada(): bool {
		return $this->sustentoAprobado && $this->puntuacionTotal >= $this->umbral;
	}

	/**
	 * @return array{puntuacionTotal: int, umbral: int, sustentoAprobado: bool, detalle: list<string>}
	 */
	public function aArray(): array {
		return array(
			'puntuacionTotal'  => $this->puntuacionTotal,
			'umbral'           => $this->umbral,
			'sustentoAprobado' => $this->sustentoAprobado,
			'detalle'          => $this->detalle,
		);
	}

	/**
	 * @param array{puntuacionTotal: int, umbral: int, sustentoAprobado: bool, detalle: list<string>} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['puntuacionTotal'], $datos['umbral'], $datos['sustentoAprobado'], $datos['detalle'] );
	}
}
