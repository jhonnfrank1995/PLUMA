<?php

declare(strict_types=1);

namespace Pluma\Datos;

use wpdb;

/**
 * Candado global vía `GET_LOCK()`/`RELEASE_LOCK()` de MySQL: es un candado
 * de sesión, no de fila u opción — si el proceso PHP muere a mitad de lote
 * (timeout, kill -9), la conexión a la base de datos se cierra y MySQL
 * libera el candado automáticamente, sin depender de que un `finally` llegue
 * a ejecutarse (que no ocurre si `max_execution_time` mata el proceso en
 * seco). Más robusto que un TTL manual sobre una opción para exactamente el
 * riesgo que el pre-mortem de la Etapa 1 identificó como #1.
 */
final class CandadoGlobal implements CandadoGlobalInterface {

	private const NOMBRE           = 'pluma_motor';
	private const TIMEOUT_SEGUNDOS = 0;

	public function __construct( private readonly wpdb $wpdb ) {
	}

	public function adquirir(): bool {
		$sql = $this->wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', self::NOMBRE, self::TIMEOUT_SEGUNDOS );
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ya se preparó arriba.
		$resultado = $this->wpdb->get_var( $sql );

		return '1' === $resultado;
	}

	public function liberar(): void {
		$sql = $this->wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::NOMBRE );
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ya se preparó arriba.
		$this->wpdb->query( $sql );
	}
}
