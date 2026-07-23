<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use RuntimeException;

/**
 * Cifrado simétrico para secretos en reposo (GOVERNANCE §3.2): las llaves de
 * API jamás se guardan en texto plano en `wp_options`.
 *
 * La clave se deriva de las salts de `wp-config.php` (AUTH_KEY +
 * SECURE_AUTH_KEY) vía hash genérico de libsodium: el material de clave vive
 * en el sistema de archivos del cliente, nunca en la base de datos — un
 * volcado de la BD no expone las llaves. Contrapartida documentada: si el
 * cliente rota sus salts, los secretos guardados se vuelven ilegibles y
 * deberá re-ingresarlos (fallo recuperable, sin pérdida de datos editoriales).
 */
final class Cifrado {

	/**
	 * Cifra y devuelve un sobre versionado apto para persistir en una opción.
	 *
	 * @throws RuntimeException si las salts no están definidas.
	 */
	public static function cifrar( string $textoPlano ): string {
		$nonce   = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cifrado = sodium_crypto_secretbox( $textoPlano, $nonce, self::clave() );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- codificación binario-a-texto para persistir en una opción de texto, no ofuscación de código.
		return 'pluma_v1:' . base64_encode( $nonce . $cifrado );
	}

	/**
	 * Descifra un sobre creado por {@see cifrar()}. Devuelve null si el sobre
	 * es inválido o la clave cambió (salts rotadas) — el llamador decide si
	 * notificar "re-ingresa tu llave".
	 */
	public static function descifrar( string $sobre ): ?string {
		if ( ! str_starts_with( $sobre, 'pluma_v1:' ) ) {
			return null;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decodificación del sobre generado por cifrar(), no ofuscación de código.
		$binario = base64_decode( substr( $sobre, 9 ), true );

		if ( false === $binario || strlen( $binario ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}

		$nonce   = substr( $binario, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cifrado = substr( $binario, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$textoPlano = sodium_crypto_secretbox_open( $cifrado, $nonce, self::clave() );

		return false !== $textoPlano ? $textoPlano : null;
	}

	/**
	 * @throws RuntimeException
	 */
	private static function clave(): string {
		if ( ! defined( 'AUTH_KEY' ) || ! defined( 'SECURE_AUTH_KEY' ) || '' === AUTH_KEY . SECURE_AUTH_KEY ) {
			throw new RuntimeException( 'PLUMA: las salts AUTH_KEY/SECURE_AUTH_KEY no están definidas en wp-config.php.' );
		}

		return sodium_crypto_generichash( AUTH_KEY . SECURE_AUTH_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}
}
