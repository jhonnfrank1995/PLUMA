<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * Salida de `ExtractorPalabrasClave` (Libro Cap. 6.2): la principal debe
 * aparecer en el titular, la URL, el primer párrafo y al menos un H2 — la
 * verificación de esa presencia es responsabilidad de `MotorSeo`, no de este DTO.
 */
final readonly class PalabrasClave {

	/**
	 * @param list<string> $secundarias
	 */
	public function __construct(
		public string $principal,
		public array $secundarias,
	) {
	}

	/**
	 * @return array{principal: string, secundarias: list<string>}
	 */
	public function aArray(): array {
		return array(
			'principal'   => $this->principal,
			'secundarias' => $this->secundarias,
		);
	}

	/**
	 * @param array{principal: string, secundarias: list<string>} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['principal'], $datos['secundarias'] );
	}
}
