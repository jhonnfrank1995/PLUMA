<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Vertical que un periodista puede firmar, con nivel de dominio 1–5
 * (Libro Cap. 5.2, Capa 1 — Identidad). El Paso 2 del Algoritmo de Decisión
 * Editorial pondera el dominio del vertical con peso alto en la asignación.
 */
final readonly class Especialidad {

	public function __construct(
		public string $vertical,
		public int $nivelDominio,
	) {
	}

	/**
	 * @return array{vertical: string, nivelDominio: int}
	 */
	public function aArray(): array {
		return array(
			'vertical'     => $this->vertical,
			'nivelDominio' => $this->nivelDominio,
		);
	}

	/**
	 * @param array{vertical: string, nivelDominio: int} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['vertical'], $datos['nivelDominio'] );
	}
}
