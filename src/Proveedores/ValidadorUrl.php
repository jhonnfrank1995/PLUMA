<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * SSRF (GOVERNANCE §3.3): toda URL externa se valida ANTES de cualquier
 * `wp_remote_get`. Esquema https, host resoluble y sin apuntar a rangos de
 * red privados/reservados.
 */
final class ValidadorUrl {

	public static function esSegura( string $url ): bool {
		$partes = wp_parse_url( $url );

		if ( false === $partes || ! isset( $partes['scheme'], $partes['host'] ) ) {
			return false;
		}

		if ( 'https' !== $partes['scheme'] ) {
			return false;
		}

		$host = $partes['host'];
		$ip   = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : gethostbyname( $host );

		if ( $ip === $host && ! filter_var( $host, FILTER_VALIDATE_IP ) ) {
			// gethostbyname() no pudo resolver el host.
			return false;
		}

		return false !== filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
}
