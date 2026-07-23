<?php

declare(strict_types=1);

namespace Pluma\Investigacion;

/**
 * Expediente de investigación de una Pieza (Libro Cap. 4): el redactor —
 * mecánico en esta Etapa, sintético desde la Etapa 2 — solo conoce lo que
 * está aquí (GOVERNANCE §2.4, anti-alucinación).
 */
final readonly class Expediente {

	/**
	 * @param list<HechoFuente> $hechos
	 */
	public function __construct(
		public string $tendenciaOrigen,
		public array $hechos,
	) {
	}

	/**
	 * @return array{tendenciaOrigen: string, hechos: list<array{extracto: string, url: string, fecha: string, nivel: string}>}
	 */
	public function aArray(): array {
		return array(
			'tendenciaOrigen' => $this->tendenciaOrigen,
			'hechos'          => array_map( static fn ( HechoFuente $h ): array => $h->aArray(), $this->hechos ),
		);
	}

	/**
	 * @param array{tendenciaOrigen: string, hechos: list<array{extracto: string, url: string, fecha: string, nivel: string}>} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['tendenciaOrigen'],
			array_map( static fn ( array $h ): HechoFuente => HechoFuente::desdeArray( $h ), $datos['hechos'] ),
		);
	}
}
