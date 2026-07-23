<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Resultado consolidado de `EvaluadorCompuertas` (Libro Cap. 8.4): "toda
 * decisión de compuerta... se escribe en el expediente de la Pieza". El
 * llamador (Orquestador) decide la transición de estado real vía
 * `Transicionador` — esta clase es pura evaluación, no toca `$wpdb`.
 */
final readonly class ResultadoEvaluacion {

	/**
	 * @param list<string> $motivos
	 */
	public function __construct(
		public bool $aprobada,
		public bool $retenida,
		public array $motivos,
		public ModoOperacion $modoEfectivo,
		public DiagnosticoCalidad $calidad,
		public DiagnosticoRiesgo $riesgo,
		public DiagnosticoOriginalidad $originalidad,
	) {
	}

	/**
	 * @return array{aprobada: bool, retenida: bool, motivos: list<string>, modoEfectivo: string, calidad: array{puntuacionTotal: int, umbral: int, sustentoAprobado: bool, detalle: list<string>}, riesgo: array{implicaTragedia: bool, implicaMenores: bool, implicaSalud: bool, implicaViolencia: bool, riesgoDifamacion: bool, detalleDifamacion: string, hechosDisputadosSinSenalar: bool, temaRegulado: ?string}, originalidad: array{solapamientoConFuentes: bool, solapamientoConSitioPropio: bool, ratioGananciaInformacion: float, umbralGananciaMinima: float}}
	 */
	public function aArray(): array {
		return array(
			'aprobada'     => $this->aprobada,
			'retenida'     => $this->retenida,
			'motivos'      => $this->motivos,
			'modoEfectivo' => $this->modoEfectivo->value,
			'calidad'      => $this->calidad->aArray(),
			'riesgo'       => $this->riesgo->aArray(),
			'originalidad' => $this->originalidad->aArray(),
		);
	}

	/**
	 * @param array{aprobada: bool, retenida: bool, motivos: list<string>, modoEfectivo: string, calidad: array{puntuacionTotal: int, umbral: int, sustentoAprobado: bool, detalle: list<string>}, riesgo: array{implicaTragedia: bool, implicaMenores: bool, implicaSalud: bool, implicaViolencia: bool, riesgoDifamacion: bool, detalleDifamacion: string, hechosDisputadosSinSenalar: bool, temaRegulado: ?string}, originalidad: array{solapamientoConFuentes: bool, solapamientoConSitioPropio: bool, ratioGananciaInformacion: float, umbralGananciaMinima: float}} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['aprobada'],
			$datos['retenida'],
			$datos['motivos'],
			ModoOperacion::from( $datos['modoEfectivo'] ),
			DiagnosticoCalidad::desdeArray( $datos['calidad'] ),
			DiagnosticoRiesgo::desdeArray( $datos['riesgo'] ),
			DiagnosticoOriginalidad::desdeArray( $datos['originalidad'] )
		);
	}
}
