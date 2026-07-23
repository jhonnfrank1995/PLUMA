<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Pipeline\Pieza;

/**
 * Contrato de redacción (Libro Cap. 5). Recibe la Pieza completa —no solo el
 * expediente— porque el periodista sintético necesita su id para persistir
 * la asignación y la Ficha de Decisión Editorial antes de redactar
 * (`Pluma\Datos\RepositorioPiezasInterface`).
 *
 * `RedactorConFallbackMecanico` es la implementación de producción: intenta
 * `RedactorSintetico` y cae a `RedactorMecanico` cuando no hay presupuesto o
 * credenciales configuradas (CLAUDE.md § Contrato del Proveedor de Lenguaje;
 * decisión explícita del propietario: "notificar y usar el redactor
 * mecánico"). `RedactorMecanico` ya no implementa esta interfaz directamente
 * — es un colaborador interno del fallback, no un redactor de producción
 * autónomo desde la Etapa 2.
 */
interface RedactorInterface {

	/**
	 * @throws DecisionEditorialException fallo no recuperable del Algoritmo de Decisión Editorial (no confundir con el escenario de fallback autorizado).
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException si no hay fallback aplicable (fallo técnico real, no "sin presupuesto/credenciales").
	 */
	public function redactar( Pieza $pieza ): ResultadoRedaccion;
}
