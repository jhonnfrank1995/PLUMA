<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Legibilidad determinista (Libro Cap. 8.1): "longitud de frases y párrafos
 * según el registro del periodista". Heurística simple y documentada —
 * promedio de palabras por frase — en vez de una fórmula de legibilidad en
 * español (Fernández-Huerta) que exigiría conteo silábico y no aporta
 * precisión adicional relevante para un gate de 0–100.
 *
 * Puntuación máxima con frases de 12–22 palabras (rango cómodo para
 * cualquier registro); decae linealmente hacia los extremos.
 */
final class VerificadorLegibilidad {

	private const PUNTUACION_MAXIMA     = 20;
	private const PALABRAS_OPTIMO_MIN   = 12;
	private const PALABRAS_OPTIMO_MAX   = 22;
	private const PALABRAS_LIMITE_CORTO = 5;
	private const PALABRAS_LIMITE_LARGO = 40;

	public function puntuar( string $texto ): int {
		$promedio = $this->promedioPalabrasPorFrase( $texto );

		if ( null === $promedio ) {
			return 0;
		}

		if ( $promedio >= self::PALABRAS_OPTIMO_MIN && $promedio <= self::PALABRAS_OPTIMO_MAX ) {
			return self::PUNTUACION_MAXIMA;
		}

		if ( $promedio < self::PALABRAS_OPTIMO_MIN ) {
			if ( $promedio <= self::PALABRAS_LIMITE_CORTO ) {
				return 0;
			}

			$proporcion = ( $promedio - self::PALABRAS_LIMITE_CORTO ) / ( self::PALABRAS_OPTIMO_MIN - self::PALABRAS_LIMITE_CORTO );

			return (int) round( self::PUNTUACION_MAXIMA * $proporcion );
		}

		if ( $promedio >= self::PALABRAS_LIMITE_LARGO ) {
			return 0;
		}

		$proporcion = ( self::PALABRAS_LIMITE_LARGO - $promedio ) / ( self::PALABRAS_LIMITE_LARGO - self::PALABRAS_OPTIMO_MAX );

		return (int) round( self::PUNTUACION_MAXIMA * $proporcion );
	}

	private function promedioPalabrasPorFrase( string $texto ): ?float {
		$frasesCrudas = preg_split( '/(?<=[.!?])\s+/u', $texto );
		$frases       = array_values(
			array_filter(
				false !== $frasesCrudas ? $frasesCrudas : array(),
				static fn ( string $f ): bool => '' !== trim( $f )
			)
		);

		if ( array() === $frases ) {
			return null;
		}

		$totalPalabras = 0;

		foreach ( $frases as $frase ) {
			$palabrasCrudas = preg_split( '/\s+/u', trim( $frase ) );
			$palabras       = false !== $palabrasCrudas ? $palabrasCrudas : array();
			$totalPalabras += count( array_filter( $palabras, static fn ( string $p ): bool => '' !== $p ) );
		}

		return $totalPalabras / count( $frases );
	}
}
