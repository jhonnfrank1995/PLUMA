<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use wpdb;

final class RepositorioBitacora implements RepositorioBitacoraInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_bitacora_motor';
	}

	public function iniciarEjecucion( DateTimeImmutable $ahora ): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'iniciada_en'       => $ahora->format( 'Y-m-d H:i:s' ),
				'finalizada_en'     => null,
				'lotes_procesados'  => 0,
				'errores'           => null,
				'candado_adquirido' => 1,
			),
			array( '%s', '%s', '%d', '%s', '%d' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function finalizarEjecucion( int $id, DateTimeImmutable $ahora, int $lotesProcesados, array $errores ): void {
		$this->wpdb->update(
			$this->tabla(),
			array(
				'finalizada_en'    => $ahora->format( 'Y-m-d H:i:s' ),
				'lotes_procesados' => $lotesProcesados,
				'errores'          => array() !== $errores ? wp_json_encode( $errores ) : null,
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
	}
}
