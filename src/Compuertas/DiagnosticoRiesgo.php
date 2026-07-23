<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Diagnóstico de la Compuerta de Riesgo (Libro Cap. 8.2). `implicaTragedia`
 * se hereda de la clasificación del Paso 1 del Algoritmo de Decisión
 * Editorial (Etapa 2, `Pluma\Redaccion\ClasificacionNoticia`) — no se
 * re-pregunta al proveedor de lenguaje algo que el sistema ya sabe.
 */
final readonly class DiagnosticoRiesgo {

	public function __construct(
		public bool $implicaTragedia,
		public bool $implicaMenores,
		public bool $implicaSalud,
		public bool $implicaViolencia,
		public bool $riesgoDifamacion,
		public string $detalleDifamacion,
		public bool $hechosDisputadosSinSenalar,
		public ?TemaRegulado $temaRegulado,
	) {
	}

	/**
	 * Sensibilidad temática (Libro Cap. 8.2/2.4): fuerza degradación de modo
	 * y bloqueo absoluto de sátira, por encima de cualquier configuración.
	 */
	public function requiereDegradacionPorSensibilidad(): bool {
		return $this->implicaTragedia || $this->implicaMenores || $this->implicaSalud || $this->implicaViolencia;
	}

	/**
	 * Riesgo legal/reputacional que el sistema nunca decide solo (Libro
	 * Cap. 8.2): difamación o hechos disputados presentados como consenso.
	 */
	public function requiereRetencionParaHumano(): bool {
		return $this->riesgoDifamacion || $this->hechosDisputadosSinSenalar;
	}

	/**
	 * @return array{implicaTragedia: bool, implicaMenores: bool, implicaSalud: bool, implicaViolencia: bool, riesgoDifamacion: bool, detalleDifamacion: string, hechosDisputadosSinSenalar: bool, temaRegulado: ?string}
	 */
	public function aArray(): array {
		return array(
			'implicaTragedia'            => $this->implicaTragedia,
			'implicaMenores'             => $this->implicaMenores,
			'implicaSalud'               => $this->implicaSalud,
			'implicaViolencia'           => $this->implicaViolencia,
			'riesgoDifamacion'           => $this->riesgoDifamacion,
			'detalleDifamacion'          => $this->detalleDifamacion,
			'hechosDisputadosSinSenalar' => $this->hechosDisputadosSinSenalar,
			'temaRegulado'               => $this->temaRegulado?->value,
		);
	}

	/**
	 * @param array{implicaTragedia: bool, implicaMenores: bool, implicaSalud: bool, implicaViolencia: bool, riesgoDifamacion: bool, detalleDifamacion: string, hechosDisputadosSinSenalar: bool, temaRegulado: ?string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['implicaTragedia'],
			$datos['implicaMenores'],
			$datos['implicaSalud'],
			$datos['implicaViolencia'],
			$datos['riesgoDifamacion'],
			$datos['detalleDifamacion'],
			$datos['hechosDisputadosSinSenalar'],
			null !== $datos['temaRegulado'] ? TemaRegulado::from( $datos['temaRegulado'] ) : null
		);
	}
}
