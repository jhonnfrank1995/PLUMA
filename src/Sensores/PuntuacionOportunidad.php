<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Puntuación de Oportunidad (Libro Cap. 3.3): Velocidad 35% + Afinidad 30% +
 * Hueco competitivo 20% + Vida útil 15%.
 *
 * Etapa 1 solo puede calcular Velocidad y Afinidad — Hueco competitivo exige
 * datos de SERP (Motor SEO) y Vida útil exige clasificación de perecibilidad
 * que aún no existe. El total se normaliza sobre el 65% disponible para
 * seguir siendo comparable en una escala 0-100 (docs/deuda.md: PLUMA-E1-1).
 */
final readonly class PuntuacionOportunidad {

	private const PESO_VELOCIDAD  = 0.35;
	private const PESO_AFINIDAD   = 0.30;
	private const PESO_DISPONIBLE = self::PESO_VELOCIDAD + self::PESO_AFINIDAD;

	private function __construct(
		public float $velocidad,
		public float $afinidad,
		public float $total,
	) {
	}

	public static function calcular( float $velocidad, float $afinidad ): self {
		$velocidad = max( 0.0, min( 100.0, $velocidad ) );
		$afinidad  = max( 0.0, min( 100.0, $afinidad ) );

		$total = ( ( $velocidad * self::PESO_VELOCIDAD ) + ( $afinidad * self::PESO_AFINIDAD ) ) / self::PESO_DISPONIBLE;

		return new self( $velocidad, $afinidad, round( $total, 2 ) );
	}
}
