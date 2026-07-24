<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use DateTimeImmutable;

/**
 * Contrato con la API real de Google Search Console (Libro Cap. 6.4). Único
 * lugar del plugin con permiso de hablar OAuth2/HTTP con Google para esta
 * señal (CLAUDE.md § Ley de Arquitectura) — `Pluma\Datos` y `Pluma\Admin`
 * consumen esto, jamás un cliente HTTP directo.
 */
interface ProveedorSearchConsoleInterface {

	/**
	 * URL de consentimiento de Google (`access_type=offline` para garantizar
	 * un `refresh_token`). `$state` es un valor propio de un solo uso que el
	 * llamador debe verificar al recibir el callback (anti-CSRF).
	 */
	public function urlAutorizacion( string $clientId, string $redirectUri, string $state ): string;

	/**
	 * @throws ProveedorSearchConsoleException si Google rechaza el código o la petición falla.
	 */
	public function intercambiarCodigo( string $clientId, string $clientSecret, string $code, string $redirectUri ): TokensOAuth;

	/**
	 * @throws ProveedorSearchConsoleException si el refresh_token ya no es válido o la petición falla.
	 */
	public function refrescarAccessToken( string $clientId, string $clientSecret, string $refreshToken ): TokensOAuth;

	/**
	 * @return list<array{siteUrl: string, permissionLevel: string}>
	 *
	 * @throws ProveedorSearchConsoleException
	 */
	public function listarSitios( string $accessToken ): array;

	/**
	 * @return list<FilaAnaliticaSearchConsole>
	 *
	 * @throws ProveedorSearchConsoleException
	 */
	public function consultarAnalitica(
		string $accessToken,
		string $siteUrl,
		DateTimeImmutable $desde,
		DateTimeImmutable $hasta
	): array;

	public function circuitoAbierto(): bool;
}
