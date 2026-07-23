<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Investigacion\Expediente;

/**
 * Palabra clave principal y secundarias (Libro Cap. 6.2). La principal es
 * siempre `Expediente->tendenciaOrigen` — el Radar ya sabe qué busca la
 * gente (Libro Cap. 3); no se re-adivina con el proveedor de lenguaje.
 *
 * Las secundarias son una heurística determinista de frecuencia de palabras
 * sobre los extractos del expediente, en el mismo espíritu que
 * `Pluma\Redaccion\VerificadorNGramas`: no es NLP real (sin lematización ni
 * detección de entidades), es una aproximación barata y explicable — el
 * Taxónomo (Etapa 3) es quien hace reconciliación de entidades seria.
 */
final class ExtractorPalabrasClave {

	private const MAXIMO_SECUNDARIAS      = 5;
	private const LONGITUD_MINIMA_PALABRA = 4;

	/**
	 * Palabras funcionales del español sin valor como palabra clave.
	 *
	 * @var list<string>
	 */
	private const PALABRAS_VACIAS = array(
		'para',
		'como',
		'pero',
		'esto',
		'esta',
		'estos',
		'estas',
		'entre',
		'sobre',
		'desde',
		'hasta',
		'donde',
		'cuando',
		'porque',
		'segun',
		'contra',
		'tras',
		'también',
		'tambien',
		'mientras',
		'aunque',
		'todo',
		'toda',
		'todos',
		'todas',
		'otro',
		'otra',
		'otros',
		'otras',
		'unos',
		'unas',
		'este',
		'ese',
		'esa',
		'esos',
		'esas',
	);

	public function extraer( Expediente $expediente ): PalabrasClave {
		$frecuencias       = array();
		$palabrasPrincipal = $this->palabras( $expediente->tendenciaOrigen );

		foreach ( $expediente->hechos as $hecho ) {
			foreach ( $this->palabras( $hecho->extracto ) as $palabra ) {
				if ( in_array( $palabra, $palabrasPrincipal, true ) ) {
					continue;
				}

				$frecuencias[ $palabra ] = ( $frecuencias[ $palabra ] ?? 0 ) + 1;
			}
		}

		arsort( $frecuencias );

		$secundarias = array_slice( array_keys( $frecuencias ), 0, self::MAXIMO_SECUNDARIAS );

		return new PalabrasClave( $expediente->tendenciaOrigen, array_values( $secundarias ) );
	}

	/**
	 * @return list<string>
	 */
	private function palabras( string $texto ): array {
		$limpio         = (string) preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower( $texto ) );
		$palabrasCrudas = preg_split( '/\s+/u', trim( $limpio ) );
		$candidatas     = false !== $palabrasCrudas ? $palabrasCrudas : array();

		return array_values(
			array_filter(
				$candidatas,
				static fn ( string $p ): bool => mb_strlen( $p ) >= self::LONGITUD_MINIMA_PALABRA && ! in_array( $p, self::PALABRAS_VACIAS, true )
			)
		);
	}
}
