<?php

declare(strict_types=1);

namespace Pluma\Investigacion;

use DateTimeImmutable;

/**
 * Un hecho del expediente con su procedencia (GOVERNANCE §2.5): extracto
 * acotado, url y fecha para citar y enlazar — jamás para reproducir.
 */
final readonly class HechoFuente {

	public function __construct(
		public string $extracto,
		public string $url,
		public DateTimeImmutable $fecha,
		public NivelVerificacion $nivel,
	) {
	}

	/**
	 * @return array{extracto: string, url: string, fecha: string, nivel: string}
	 */
	public function aArray(): array {
		return array(
			'extracto' => $this->extracto,
			'url'      => $this->url,
			'fecha'    => $this->fecha->format( DATE_ATOM ),
			'nivel'    => $this->nivel->value,
		);
	}

	/**
	 * @param array{extracto: string, url: string, fecha: string, nivel: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['extracto'],
			$datos['url'],
			new DateTimeImmutable( $datos['fecha'] ),
			NivelVerificacion::from( $datos['nivel'] ),
		);
	}
}
