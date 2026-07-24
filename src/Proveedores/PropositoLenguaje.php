<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Propósitos de una petición al proveedor de lenguaje
 * (pl-proveedor-ia, `references/contrato-lenguaje.md`). El Enrutador asigna
 * modelo por propósito: clasificar barato, redactar premium.
 */
enum PropositoLenguaje: string {

	case Clasificar   = 'clasificar';
	case Angulos      = 'angulos';
	case Redactar     = 'redactar';
	case Corregir     = 'corregir';
	case Titulares    = 'titulares';
	case BloqueEditor = 'bloque_editor';
	/**
	 * Vista previa en vivo del Estudio de Conducta (Libro Cap. 10.2): un
	 * párrafo corto que se re-redacta al mover un dial. Deliberadamente NO
	 * premium — es una demostración, no una Pieza real, y consume del mismo
	 * presupuesto diario compartido (decisión del propietario, 2026-07-23).
	 */
	case VistaPrevia = 'vista_previa';
	/**
	 * Huella semántica del Radar (Libro Cap. 3.4, Etapa 5): ¿una tendencia
	 * nueva es la misma historia que una ya procesada, o su evolución?
	 * Económico y determinista, igual que `Clasificar` — es una comparación
	 * estructurada, no redacción.
	 */
	case CompararHistorias = 'comparar_historias';

	public function esPremium(): bool {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: $this en un método de enum (PHP 8.1) es válido; el sniff aún no reconoce enums.
		return match ( $this ) {
			self::Redactar, self::Corregir, self::BloqueEditor => true,
			self::Clasificar, self::Angulos, self::Titulares, self::VistaPrevia, self::CompararHistorias => false,
		};
	}

	/**
	 * Temperatura por propósito: tareas de clasificación deterministas,
	 * redacción con espacio creativo (contrato-lenguaje.md: "limites...
	 * temperatura por propósito").
	 */
	public function temperatura(): float {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: $this en un método de enum (PHP 8.1) es válido; el sniff aún no reconoce enums.
		return match ( $this ) {
			self::Clasificar, self::CompararHistorias => 0.0,
			self::Corregir => 0.2,
			self::Angulos, self::Titulares => 0.8,
			self::Redactar, self::BloqueEditor, self::VistaPrevia => 0.7,
		};
	}
}
