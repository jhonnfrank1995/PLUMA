<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Extrae un objeto JSON del contenido devuelto por el proveedor de lenguaje.
 * Los modelos frecuentemente envuelven el JSON en una cerca de código
 * Markdown (```json ... ```) o añaden texto antes/después pese a la
 * instrucción de responder solo JSON; este extractor tolera ambos casos
 * antes de rendirse.
 */
final class ExtractorJsonLlm {

	/**
	 * @return array<string, mixed>
	 * @throws DecisionEditorialException si no se encuentra un objeto JSON válido.
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
			throw new DecisionEditorialException( 'El proveedor de lenguaje no devolvió un objeto JSON reconocible.' );
		}

		$json  = substr( $candidato, $inicio, $fin - $inicio + 1 );
		$datos = json_decode( $json, true );

		if ( ! is_array( $datos ) ) {
			throw new DecisionEditorialException( 'El proveedor de lenguaje devolvió JSON con formato inesperado.' );
		}

		/** @var array<string, mixed> $datos */
		return $datos;
	}
}
