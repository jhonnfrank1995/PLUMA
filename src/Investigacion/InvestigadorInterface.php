<?php

declare(strict_types=1);

namespace Pluma\Investigacion;

/**
 * Contrato del Investigador (Libro Cap. 4). La implementación mecánica de
 * Etapa 1 construye el expediente directamente de la señal del Sensor, sin
 * el protocolo completo de 5 pasos (fuente primaria, 4-8 coberturas
 * secundarias, triangulación) — deuda registrada, se amplía cuando el
 * Sensor incorpore fuentes propias multi-medio.
 */
interface InvestigadorInterface {

	/**
	 * @param list<array{titulo: string, url: string, fuente: string}> $articulosRelacionados
	 */
	public function investigar( string $termino, array $articulosRelacionados ): Expediente;
}
