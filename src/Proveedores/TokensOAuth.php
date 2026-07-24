<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Tokens de un intercambio OAuth2 (Libro Cap. 6.4, bucle de Search Console).
 * `refreshToken` puede ser `null` en una respuesta de refresco (Google no
 * siempre lo reemite) — el llamador conserva el que ya tenía guardado.
 */
final readonly class TokensOAuth {

	public function __construct(
		public string $accessToken,
		public ?string $refreshToken,
		public int $expiraEnSegundos,
	) {
	}
}
