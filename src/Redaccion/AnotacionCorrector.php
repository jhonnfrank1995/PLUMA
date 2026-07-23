<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Resultado del Corrector Interno para un punto de su lista de verificación
 * (Libro Cap. 5.6).
 */
final readonly class AnotacionCorrector {

	public function __construct(
		public PuntoCorrector $punto,
		public bool $aprobado,
		public string $detalle,
	) {
	}

	/**
	 * @return array{punto: string, aprobado: bool, detalle: string}
	 */
	public function aArray(): array {
		return array(
			'punto'    => $this->punto->value,
			'aprobado' => $this->aprobado,
			'detalle'  => $this->detalle,
		);
	}

	/**
	 * @param array{punto: string, aprobado: bool, detalle: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			PuntoCorrector::from( $datos['punto'] ),
			$datos['aprobado'],
			$datos['detalle']
		);
	}
}
