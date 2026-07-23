<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Kernel;

use Pluma\Kernel\Cifrado;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use RuntimeException;

/**
 * Cada test corre en proceso separado: las salts AUTH_KEY/SECURE_AUTH_KEY son
 * constantes globales inmutables una vez definidas, y necesitamos estados
 * distintos (no definidas, definidas, "rotadas") entre casos.
 *
 * @covers \Pluma\Kernel\Cifrado
 */
final class CifradoTest extends CasoDePruebaUnitario {

	/**
	 * @runInSeparateProcess
	 */
	public function test_cifra_y_descifra_un_texto_en_una_ida_y_vuelta(): void {
		define( 'AUTH_KEY', 'clave-app-de-prueba' );
		define( 'SECURE_AUTH_KEY', 'clave-secure-de-prueba' );

		$sobre = Cifrado::cifrar( 'sk-or-v1-secreto-de-prueba' );

		self::assertStringStartsWith( 'pluma_v1:', $sobre );
		self::assertSame( 'sk-or-v1-secreto-de-prueba', Cifrado::descifrar( $sobre ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_dos_cifrados_del_mismo_texto_no_son_identicos(): void {
		define( 'AUTH_KEY', 'clave-app-de-prueba' );
		define( 'SECURE_AUTH_KEY', 'clave-secure-de-prueba' );

		// El nonce aleatorio por llamada impide que un volcado de la BD revele
		// secretos repetidos por comparación de sobres idénticos.
		self::assertNotSame( Cifrado::cifrar( 'mismo-secreto' ), Cifrado::cifrar( 'mismo-secreto' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_descifrar_devuelve_null_si_falta_el_prefijo_de_version(): void {
		define( 'AUTH_KEY', 'clave-app-de-prueba' );
		define( 'SECURE_AUTH_KEY', 'clave-secure-de-prueba' );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- codificación binario-a-texto para el caso de prueba, no ofuscación de código.
		self::assertNull( Cifrado::descifrar( base64_encode( 'cualquier-cosa' ) ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_descifrar_devuelve_null_si_el_binario_esta_corrupto(): void {
		define( 'AUTH_KEY', 'clave-app-de-prueba' );
		define( 'SECURE_AUTH_KEY', 'clave-secure-de-prueba' );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- codificación binario-a-texto para el caso de prueba, no ofuscación de código.
		self::assertNull( Cifrado::descifrar( 'pluma_v1:' . base64_encode( 'demasiado-corto' ) ) );
		self::assertNull( Cifrado::descifrar( 'pluma_v1:no-es-base64-valido-@@@' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_descifrar_devuelve_null_si_las_salts_rotaron(): void {
		// Sobre cifrado con una clave distinta a la que tendrá el proceso al
		// descifrar — simula la rotación de salts documentada en Cifrado::clave().
		$claveVieja = sodium_crypto_generichash( 'salt-app-viejasalt-secure-vieja', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
		$nonce      = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cifrado    = sodium_crypto_secretbox( 'secreto-antes-de-rotar', $nonce, $claveVieja );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- codificación binario-a-texto para el caso de prueba, no ofuscación de código.
		$sobre = 'pluma_v1:' . base64_encode( $nonce . $cifrado );

		define( 'AUTH_KEY', 'salt-app-nueva' );
		define( 'SECURE_AUTH_KEY', 'salt-secure-nueva' );

		self::assertNull( Cifrado::descifrar( $sobre ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_cifrar_lanza_excepcion_si_las_salts_no_estan_definidas(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageMatches( '/AUTH_KEY/' );

		Cifrado::cifrar( 'no-deberia-poder-cifrar-esto' );
	}
}
