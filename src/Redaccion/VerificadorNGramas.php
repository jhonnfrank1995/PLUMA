<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Investigacion\Expediente;

/**
 * Punto 3 del Corrector Interno (Libro Cap. 5.6): "¿Hay frases con
 * similitud sospechosa con los extractos de fuentes?" — verificación
 * determinista de solapamiento n-grama, sin proveedor de lenguaje. Cualquier
 * secuencia de {@see self::TAMANO_NGRAMA} palabras o más copiada
 * textualmente de un extracto del expediente reprueba el punto.
 */
final class VerificadorNGramas {

	private const TAMANO_NGRAMA = 8;

	public function verificar( Expediente $expediente, string $cuerpo ): AnotacionCorrector {
		$ngramasCuerpo = $this->ngramas( $cuerpo );

		if ( array() === $ngramasCuerpo ) {
			return new AnotacionCorrector( PuntoCorrector::SolapamientoNGrama, true, 'Cuerpo demasiado corto para evaluar solapamiento; sin riesgo.' );
		}

		foreach ( $expediente->hechos as $hecho ) {
			$compartidos = array_intersect( $ngramasCuerpo, $this->ngramas( $hecho->extracto ) );

			if ( array() !== $compartidos ) {
				return new AnotacionCorrector(
					PuntoCorrector::SolapamientoNGrama,
					false,
					sprintf( 'Secuencia de %d+ palabras copiada textualmente de una fuente (%s).', self::TAMANO_NGRAMA, $hecho->url )
				);
			}
		}

		return new AnotacionCorrector( PuntoCorrector::SolapamientoNGrama, true, 'Sin solapamiento textual sospechoso con las fuentes del expediente.' );
	}

	/**
	 * @return list<string>
	 */
	private function ngramas( string $texto ): array {
		$limpio         = (string) preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower( $texto ) );
		$palabrasCrudas = preg_split( '/\s+/u', trim( $limpio ) );
		$palabras       = array_values( array_filter( false !== $palabrasCrudas ? $palabrasCrudas : array(), static fn ( string $p ): bool => '' !== $p ) );

		$total = count( $palabras );

		if ( $total < self::TAMANO_NGRAMA ) {
			return array();
		}

		$ngramas = array();

		for ( $i = 0; $i <= $total - self::TAMANO_NGRAMA; $i++ ) {
			$ngramas[] = implode( ' ', array_slice( $palabras, $i, self::TAMANO_NGRAMA ) );
		}

		return $ngramas;
	}
}
