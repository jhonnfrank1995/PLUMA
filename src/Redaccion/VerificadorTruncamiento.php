<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Proveedores\RespuestaLenguaje;

/**
 * Contrato de `RespuestaLenguaje` (contrato-lenguaje.md): "truncada=true → el
 * llamador reintenta con límites ajustados o marca FALLIDA — jamás se usa
 * contenido truncado." Cada consumidor de `LenguajeInterface` en
 * `Pluma\Redaccion` llama esto antes de interpretar `$respuesta->contenido`.
 */
final class VerificadorTruncamiento {

	/**
	 * @throws DecisionEditorialException si la respuesta llegó truncada.
	 */
	public static function asegurar( RespuestaLenguaje $respuesta ): void {
		if ( $respuesta->truncada ) {
			throw new DecisionEditorialException(
				'El proveedor de lenguaje devolvió una respuesta truncada (límite de tokens); nunca se usa contenido truncado.'
			);
		}
	}
}
