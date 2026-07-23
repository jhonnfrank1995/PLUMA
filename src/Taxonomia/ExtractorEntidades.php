<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

use Pluma\Investigacion\Expediente;

/**
 * Extracción de entidades (Libro Cap. 7.2, punto 1): "actores, lugares,
 * eventos, conceptos" del expediente. Heurística determinista — secuencias
 * de palabras con mayúscula inicial, permitiendo conectores minúsculos
 * típicos de nombres propios en español ("Banco de la República", "Estados
 * Unidos de América") — NO es reconocimiento de entidades nombradas real
 * (sin desambiguación, sin clasificación en persona/lugar/organización).
 * Palabras funcionales que capitalizan por estar al inicio de frase (falso
 * positivo conocido de esta heurística, p. ej. "El Banco de la República")
 * se recortan del principio del candidato antes de contarlo.
 *
 * "Centralidad" (Cap. 7.2, punto 3: "no mención de paso") se aproxima como:
 * aparece 2+ veces en los hechos, o aparece en la tesis (el argumento
 * central de la pieza ya la eligió como relevante).
 */
final class ExtractorEntidades {

	private const OCURRENCIAS_MINIMAS_PARA_CENTRALIDAD = 2;

	/**
	 * Palabras funcionales que capitalizan por posición de frase, no por ser
	 * nombres propios.
	 *
	 * @var list<string>
	 */
	private const PALABRAS_FUNCIONALES = array(
		'el',
		'la',
		'los',
		'las',
		'un',
		'una',
		'unos',
		'unas',
		'este',
		'esta',
		'estos',
		'estas',
		'ese',
		'esa',
		'esos',
		'esas',
		'su',
		'sus',
		'que',
		'como',
		'pero',
		'cuando',
		'donde',
		'porque',
		'aunque',
		'mientras',
		'según',
		'segun',
		'tras',
	);

	/**
	 * Conectores típicos de nombres propios compuestos en español
	 * ("Banco de la República", "Estados Unidos de América"). Las variantes
	 * de dos palabras van primero: la alternancia de regex toma la primera
	 * que calce, así que "de la" debe probarse antes que "de" solo.
	 *
	 * @var list<string>
	 */
	private const CONECTORES = array( 'de\s+la', 'de\s+los', 'de\s+las', 'del', 'de', 'la', 'las', 'los', 'y', 'en' );

	/**
	 * @return list<string>
	 */
	public function extraer( Expediente $expediente, string $tesis ): array {
		$ocurrencias = array();

		foreach ( $expediente->hechos as $hecho ) {
			foreach ( $this->candidatos( $hecho->extracto ) as $candidato ) {
				$clave = mb_strtolower( $candidato );

				if ( ! isset( $ocurrencias[ $clave ] ) ) {
					$ocurrencias[ $clave ] = array(
						'nombre' => $candidato,
						'conteo' => 0,
					);
				}

				++$ocurrencias[ $clave ]['conteo'];
			}
		}

		$centrales = array();

		foreach ( $ocurrencias as $entrada ) {
			if ( $entrada['conteo'] >= self::OCURRENCIAS_MINIMAS_PARA_CENTRALIDAD || str_contains( $tesis, $entrada['nombre'] ) ) {
				$centrales[] = $entrada['nombre'];
			}
		}

		return $centrales;
	}

	/**
	 * @return list<string>
	 */
	private function candidatos( string $texto ): array {
		$conectores = implode( '|', self::CONECTORES );
		$patron     = '/\p{Lu}\p{L}*(?:\s+(?:' . $conectores . ')\s+\p{Lu}\p{L}*|\s+\p{Lu}\p{L}*)*/u';

		preg_match_all( $patron, $texto, $coincidencias );

		return array_values(
			array_filter(
				array_map( fn ( string $candidato ): string => $this->sinPrefijoFuncional( $candidato ), $coincidencias[0] ),
				static fn ( string $candidato ): bool => '' !== $candidato
			)
		);
	}

	/**
	 * Recorta palabras funcionales que capitalizaron por estar al inicio de
	 * frase, no por ser parte del nombre propio (p. ej. "El Banco de la
	 * República" → "Banco de la República"). Si toda la coincidencia era una
	 * sola palabra funcional, devuelve cadena vacía (se descarta).
	 */
	private function sinPrefijoFuncional( string $candidato ): string {
		$palabras = explode( ' ', $candidato );

		while ( array() !== $palabras && in_array( mb_strtolower( $palabras[0] ), self::PALABRAS_FUNCIONALES, true ) ) {
			array_shift( $palabras );
		}

		return implode( ' ', $palabras );
	}
}
