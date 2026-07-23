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

	public function esPremium(): bool {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: $this en un método de enum (PHP 8.1) es válido; el sniff aún no reconoce enums.
		return match ( $this ) {
			self::Redactar, self::Corregir, self::BloqueEditor => true,
			self::Clasificar, self::Angulos, self::Titulares => false,
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
			self::Clasificar => 0.0,
			self::Corregir => 0.2,
			self::Angulos, self::Titulares => 0.8,
			self::Redactar, self::BloqueEditor => 0.7,
		};
	}
}
