<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Extrae un objeto JSON del contenido devuelto por el proveedor de lenguaje
 * (ver `Pluma\Redaccion\ExtractorJsonLlm` — misma lógica, duplicada a
 * propósito: `Sensores` no es adyacente a `Redaccion` en la Ley de
 * Arquitectura de CLAUDE.md, y este utilitario es demasiado pequeño para
 * justificar una dependencia cruzada de capas por una abstracción compartida).
 */
final class ExtractorJsonLlm {

	/**
	 * @return array<string, mixed>
	 * @throws ComparadorHistoriasException si no se encuentra un objeto JSON válido.
	 */
	public static function extraer( string $contenido ): array {
		$candidato = trim( $contenido );

		if ( str_starts_with( $candidato, '```' ) ) {
			$sinCercaInicial = preg_replace( '/^```[a-zA-Z]*\s*/', '', $candidato );
			$candidato       = preg_replace( '/```\s*$/', '', (string) $sinCercaInicial );
			$candidato       = trim( (string) $candidato );
		}

		$inicio = strpos( $candidato, '{' );
		$fin    = strrpos( $candidato, '}' );

		if ( false === $inicio || false === $fin || $fin < $inicio ) {
			throw new ComparadorHistoriasException( 'El proveedor de lenguaje no devolvió un objeto JSON reconocible.' );
		}

		$json  = substr( $candidato, $inicio, $fin - $inicio + 1 );
		$datos = json_decode( $json, true );

		if ( ! is_array( $datos ) ) {
			throw new ComparadorHistoriasException( 'El proveedor de lenguaje devolvió JSON con formato inesperado.' );
		}

		/** @var array<string, mixed> $datos */
		return $datos;
	}
}
