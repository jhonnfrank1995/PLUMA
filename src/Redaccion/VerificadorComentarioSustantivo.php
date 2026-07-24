<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Filtro determinista (sin proveedor de lenguaje, mismo espíritu que
 * `VerificadorNGramas`/`VerificadorVoz`): descarta comentarios de ruido
 * (saludos, "primero", emojis sueltos) antes de gastar presupuesto de IA
 * en memoria de audiencia o borradores de respuesta.
 */
final class VerificadorComentarioSustantivo {

	private const LONGITUD_MINIMA = 40;

	public function esSustantivo( string $contenidoTexto ): bool {
		return mb_strlen( trim( $contenidoTexto ) ) >= self::LONGITUD_MINIMA;
	}
}
