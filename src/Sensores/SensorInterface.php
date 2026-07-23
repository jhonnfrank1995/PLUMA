<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Contrato de un Sensor de Radar (Libro Cap. 3.2): cada fuente de señal se
 * conecta con la misma interfaz — el Radar de mañana añade sensores sin
 * reescribir nada. La lógica editorial (Pipeline\Orquestador) solo conoce
 * este contrato, jamás el SDK/HTTP concreto detrás.
 */
interface SensorInterface {

	/**
	 * @return list<TendenciaDetectada>
	 */
	public function detectar(): array;

	/**
	 * Nombre corto de la fuente de señal (p. ej. "google_trends"), usado
	 * para trazabilidad en `pluma_tendencias.fuente_senal`.
	 */
	public function nombre(): string;
}
