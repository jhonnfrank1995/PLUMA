<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Lista global de vocabulario prohibido del sitio (Libro Cap. 5.3,
 * pl-periodistas §Vocabulario prohibido): clichés del medio + muletillas
 * típicas de texto generado por IA. "Esta lista es crítica y se mantiene
 * actualizada" — revisar y ampliar cada release (Libro Cap. 5.3).
 *
 * Se combina con el vocabulario prohibido propio de cada periodista
 * ({@see ReglasConducta::$vocabularioProhibido}); ninguna de las dos listas
 * sustituye a la otra.
 */
final class VocabularioProhibidoGlobal {

	/**
	 * @return list<string>
	 */
	public static function muletillasDeTextoIa(): array {
		return array(
			'es importante destacar',
			'es importante señalar',
			'cabe destacar',
			'cabe señalar',
			'en el mundo actual',
			'en la era digital',
			'a medida que avanza la tecnología',
			'sin duda alguna',
			'no cabe duda de que',
			'en conclusión',
			'en resumen',
			'es fundamental',
			'juega un papel crucial',
			'juega un papel fundamental',
			'desempeña un papel clave',
			'representa un hito',
			'marca un antes y un después',
			'en un mundo cada vez más',
			'la clave está en',
			'no hay que olvidar que',
			'como periodista sintética',
			'como modelo de lenguaje',
			'espero que esta información sea de utilidad',
		);
	}

	/**
	 * @param list<string> $vocabularioPropio
	 * @return list<string>
	 */
	public static function combinarCon( array $vocabularioPropio ): array {
		return array_values( array_unique( array_merge( self::muletillasDeTextoIa(), $vocabularioPropio ) ) );
	}
}
