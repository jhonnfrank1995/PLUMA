<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Matriz de tonos de un periodista (Libro Cap. 5.3): cruza tipo de noticia
 * con tono dominante/de apoyo y sátira permitida.
 *
 * Regla de sistema inviolable: la fila de `TipoNoticia::Tragedia` — sátira
 * BLOQUEADA — se impone siempre, sin importar qué se haya guardado o
 * configurado para ese periodista (Libro Cap. 5.3: "no es un valor de la
 * matriz: es una regla de sistema"). Esta es la primera de las dos capas
 * independientes de defensa; la segunda vive en `Pluma\Compuertas` (Etapa 3).
 */
final readonly class MatrizTonos {

	/**
	 * @param array<string, EntradaMatrizTono> $filas indexadas por `TipoNoticia::value`
	 */
	private function __construct( private array $filas ) {
	}

	/**
	 * @param list<EntradaMatrizTono> $filasConfiguradas cualquier fila de Tragedia aquí es ignorada y reemplazada por la fila de sistema
	 */
	public static function desdeFilas( array $filasConfiguradas ): self {
		$filas = array();

		foreach ( $filasConfiguradas as $fila ) {
			$filas[ $fila->tipoNoticia->value ] = $fila;
		}

		$filas[ TipoNoticia::Tragedia->value ] = self::filaSistemaTragedia();

		return new self( $filas );
	}

	public static function filaSistemaTragedia(): EntradaMatrizTono {
		return new EntradaMatrizTono(
			TipoNoticia::Tragedia,
			Tono::InformativoEmpatico,
			Tono::Analitico,
			NivelSatiraPermitida::Bloqueada
		);
	}

	/**
	 * @throws MatrizTonosIncompletaException si el periodista no tiene fila para `$tipo` (Tragedia siempre tiene, por sistema).
	 */
	public function paraTipo( TipoNoticia $tipo ): EntradaMatrizTono {
		if ( TipoNoticia::Tragedia === $tipo ) {
			return self::filaSistemaTragedia();
		}

		if ( isset( $this->filas[ $tipo->value ] ) ) {
			return $this->filas[ $tipo->value ];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
		throw new MatrizTonosIncompletaException( "La matriz de tonos no tiene fila configurada para '{$tipo->value}'." );
	}

	/**
	 * @return array<string, array{tipoNoticia: string, tonoDominante: string, tonoApoyo: string, nivelSatira: string}>
	 */
	public function aArray(): array {
		return array_map( static fn ( EntradaMatrizTono $fila ): array => $fila->aArray(), $this->filas );
	}

	/**
	 * @param array<string, array{tipoNoticia: string, tonoDominante: string, tonoApoyo: string, nivelSatira: string}> $datos
	 */
	public static function desdeArray( array $datos ): self {
		return self::desdeFilas(
			array_map( static fn ( array $fila ): EntradaMatrizTono => EntradaMatrizTono::desdeArray( $fila ), array_values( $datos ) )
		);
	}
}
