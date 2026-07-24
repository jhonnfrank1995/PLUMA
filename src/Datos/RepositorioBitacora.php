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

	public function obtenerUltima(): ?array {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- tabla interna, sin entrada de usuario que parametrizar.
		$fila = $this->wpdb->get_row( "SELECT iniciada_en, finalizada_en, lotes_procesados, errores FROM {$this->tabla()} ORDER BY iniciada_en DESC LIMIT 1", ARRAY_A );

		if ( null === $fila ) {
			return null;
		}

		return $this->filaAResumen( $fila );
	}

	public function obtenerRecientes( int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT iniciada_en, finalizada_en, lotes_procesados, errores FROM {$this->tabla()} ORDER BY iniciada_en DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): array => $this->filaAResumen( $fila ), $filas ?? array() );
	}

	public function obtenerEntre( DateTimeImmutable $desde, DateTimeImmutable $hasta ): array {
		$sql = $this->wpdb->prepare(
			"SELECT iniciada_en, finalizada_en, lotes_procesados, errores FROM {$this->tabla()} WHERE iniciada_en BETWEEN %s AND %s ORDER BY iniciada_en DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$desde->format( 'Y-m-d H:i:s' ),
			$hasta->format( 'Y-m-d H:i:s' )
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): array => $this->filaAResumen( $fila ), $filas ?? array() );
	}

	/**
	 * @param array<string, mixed> $fila
	 * @return array{iniciadaEn: string, finalizadaEn: ?string, lotesProcesados: int, errores: list<string>}
	 */
	private function filaAResumen( array $fila ): array {
		/** @var list<string> $errores */
		$errores = null !== $fila['errores'] ? ( json_decode( (string) $fila['errores'], true ) ?? array() ) : array();

		return array(
			// DATE_ATOM, no la cadena cruda de MySQL: el frontend necesita
			// fechas parseables de forma fiable como `Date` de JavaScript,
			// igual que el resto de repositorios de este proyecto.
			'iniciadaEn'      => ( new DateTimeImmutable( (string) $fila['iniciada_en'] ) )->format( DATE_ATOM ),
			'finalizadaEn'    => null !== $fila['finalizada_en'] ? ( new DateTimeImmutable( (string) $fila['finalizada_en'] ) )->format( DATE_ATOM ) : null,
			'lotesProcesados' => (int) $fila['lotes_procesados'],
			'errores'         => $errores,
		);
	}
}
