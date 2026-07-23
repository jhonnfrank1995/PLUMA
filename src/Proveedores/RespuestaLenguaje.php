<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Respuesta inmutable del proveedor de lenguaje (contrato-lenguaje.md).
 *
 * Regla: `truncada=true` → el llamador reintenta con límites ajustados o
 * marca FALLIDA — jamás se usa contenido truncado.
 */
final readonly class RespuestaLenguaje {

	public function __construct(
		public string $contenido,
		public int $tokensEntrada,
		public int $tokensSalida,
		public float $costeUsd,
		public string $proveedor,
		public string $modelo,
		public int $latenciaMs,
		public bool $truncada,
	) {
	}
}
