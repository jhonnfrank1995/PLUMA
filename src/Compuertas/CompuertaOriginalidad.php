<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;

/**
 * Compuerta de Originalidad (Libro Cap. 8.3): solapamiento contra las
 * fuentes del expediente (la del Corrector Interno en la Etapa 2, repetida
 * aquí sobre la versión final — "cinturón y tirantes"), solapamiento contra
 * el propio sitio (auto-plagio/canibalización), y una huella de ganancia de
 * información.
 *
 * La "ganancia de información" es una heurística documentada, no un
 * algoritmo de comprensión real (el propio Libro la llama "heurística"):
 * proporción de 4-gramas del texto final que NO aparecen en ningún extracto
 * del expediente. Un texto que solo reordena las fuentes tendrá una
 * proporción baja; un texto que añade tesis, contexto o cruces de datos
 * propios tendrá una proporción alta.
 */
final class CompuertaOriginalidad {

	private const TAMANO_NGRAMA_SOLAPAMIENTO = 8;
	private const TAMANO_NGRAMA_GANANCIA     = 4;
	public const OPCION_UMBRAL_GANANCIA      = 'pluma_compuerta_originalidad_umbral_ganancia';
	private const UMBRAL_GANANCIA_DEFECTO    = 0.4;

	/**
	 * @param list<string> $textosPropiosRecientes contenido en texto plano de piezas ya publicadas del sitio
	 */
	public function evaluar( Expediente $expediente, string $textoFinal, array $textosPropiosRecientes ): DiagnosticoOriginalidad {
		$extractosFuentes = array_map( static fn ( HechoFuente $hecho ): string => $hecho->extracto, $expediente->hechos );

		$solapamientoFuentes = $this->haySolapamiento( $textoFinal, $extractosFuentes, self::TAMANO_NGRAMA_SOLAPAMIENTO );
		$solapamientoSitio   = $this->haySolapamiento( $textoFinal, $textosPropiosRecientes, self::TAMANO_NGRAMA_SOLAPAMIENTO );

		return new DiagnosticoOriginalidad(
			$solapamientoFuentes,
			$solapamientoSitio,
			$this->ratioGananciaInformacion( $textoFinal, $extractosFuentes ),
			$this->umbralGananciaConfigurado()
		);
	}

	/**
	 * @param list<string> $textosComparados
	 */
	private function haySolapamiento( string $texto, array $textosComparados, int $tamanoNgrama ): bool {
		$ngramasTexto = $this->ngramas( $texto, $tamanoNgrama );

		if ( array() === $ngramasTexto ) {
			return false;
		}

		foreach ( $textosComparados as $comparado ) {
			if ( array() !== array_intersect( $ngramasTexto, $this->ngramas( $comparado, $tamanoNgrama ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<string> $extractosFuentes
	 */
	private function ratioGananciaInformacion( string $textoFinal, array $extractosFuentes ): float {
		$ngramasTexto = $this->ngramas( $textoFinal, self::TAMANO_NGRAMA_GANANCIA );

		if ( array() === $ngramasTexto ) {
			return 0.0;
		}

		$ngramasFuentes = array();

		foreach ( $extractosFuentes as $extracto ) {
			$ngramasFuentes = array_merge( $ngramasFuentes, $this->ngramas( $extracto, self::TAMANO_NGRAMA_GANANCIA ) );
		}

		$nuevos = array_diff( $ngramasTexto, $ngramasFuentes );

		return count( $nuevos ) / count( $ngramasTexto );
	}

	private function umbralGananciaConfigurado(): float {
		$umbral = get_option( self::OPCION_UMBRAL_GANANCIA, self::UMBRAL_GANANCIA_DEFECTO );

		return is_numeric( $umbral ) ? max( 0.0, min( 1.0, (float) $umbral ) ) : self::UMBRAL_GANANCIA_DEFECTO;
	}

	/**
	 * @return list<string>
	 */
	private function ngramas( string $texto, int $tamano ): array {
		$limpio         = (string) preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower( $texto ) );
		$palabrasCrudas = preg_split( '/\s+/u', trim( $limpio ) );
		$palabras       = array_values( array_filter( false !== $palabrasCrudas ? $palabrasCrudas : array(), static fn ( string $p ): bool => '' !== $p ) );

		$total = count( $palabras );

		if ( $total < $tamano ) {
			return array();
		}

		$ngramas = array();

		for ( $i = 0; $i <= $total - $tamano; $i++ ) {
			$ngramas[] = implode( ' ', array_slice( $palabras, $i, $tamano ) );
		}

		return $ngramas;
	}
}
