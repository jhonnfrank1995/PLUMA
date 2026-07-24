<?php

declare(strict_types=1);

namespace Pluma\Sensores;

use Pluma\Proveedores\RespuestaLenguaje;

/**
 * Contrato de `RespuestaLenguaje` (contrato-lenguaje.md): "truncada=true →
 * el llamador reintenta con límites ajustados o marca FALLIDA — jamás se usa
 * contenido truncado." Ver `Pluma\Redaccion\VerificadorTruncamiento` — misma
 * regla, duplicada por la misma razón de no-adyacencia de capas que
 * {@see ExtractorJsonLlm}.
 */
final class VerificadorTruncamiento {

	/**
	 * @throws ComparadorHistoriasException si la respuesta llegó truncada.
	 */
	public static function asegurar( RespuestaLenguaje $respuesta ): void {
		if ( $respuesta->truncada ) {
			throw new ComparadorHistoriasException(
				'El proveedor de lenguaje devolvió una respuesta truncada (límite de tokens); nunca se usa contenido truncado.'
			);
		}
	}
}
