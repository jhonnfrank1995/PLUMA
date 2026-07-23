<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Arquitectura argumental de la Pieza (Paso 4, Libro Cap. 5.5): gancho →
 * hechos esenciales con atribución (25–35% del texto) → desarrollo de la
 * tesis en 2–4 movimientos → contraargumento reconocido y respondido →
 * remate en el tono de apoyo. Nunca calcado de una fuente.
 */
final readonly class EsqueletoPieza {

	/**
	 * @param list<string> $movimientosArgumentales 2–4 movimientos con datos del expediente
	 */
	public function __construct(
		public string $gancho,
		public string $hechosEsencialesConAtribucion,
		public array $movimientosArgumentales,
		public string $contraargumentoReconocido,
		public string $remate,
	) {
	}

	/**
	 * @return array{gancho: string, hechosEsencialesConAtribucion: string, movimientosArgumentales: list<string>, contraargumentoReconocido: string, remate: string}
	 */
	public function aArray(): array {
		return array(
			'gancho'                        => $this->gancho,
			'hechosEsencialesConAtribucion' => $this->hechosEsencialesConAtribucion,
			'movimientosArgumentales'       => $this->movimientosArgumentales,
			'contraargumentoReconocido'     => $this->contraargumentoReconocido,
			'remate'                        => $this->remate,
		);
	}

	/**
	 * @param array{gancho: string, hechosEsencialesConAtribucion: string, movimientosArgumentales: list<string>, contraargumentoReconocido: string, remate: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['gancho'],
			$datos['hechosEsencialesConAtribucion'],
			$datos['movimientosArgumentales'],
			$datos['contraargumentoReconocido'],
			$datos['remate']
		);
	}
}
