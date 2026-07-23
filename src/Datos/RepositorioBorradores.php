<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Redaccion\AnotacionCorrector;
use Pluma\Redaccion\Borrador;
use wpdb;

/**
 * Único punto del plugin con `$wpdb` para `pluma_borradores` (CLAUDE.md § Ley
 * de Arquitectura). Ver `RepositorioPiezas` para la nota sobre los
 * `argument.type` ignorados en `$wpdb->prepare()`.
 */
final class RepositorioBorradores implements RepositorioBorradoresInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_borradores';
	}

	public function crear(
		int $piezaId,
		int $numeroCiclo,
		string $contenido,
		array $anotaciones,
		bool $aprobadoPorCorrector,
		DateTimeImmutable $ahora
	): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'pieza_id'               => $piezaId,
				'numero_ciclo'           => $numeroCiclo,
				'contenido'              => $contenido,
				'anotaciones_corrector'  => wp_json_encode( array_map( static fn ( AnotacionCorrector $a ): array => $a->aArray(), $anotaciones ) ),
				'aprobado_por_corrector' => $aprobadoPorCorrector ? 1 : 0,
				'creado_en'              => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function obtenerPorPieza( int $piezaId ): array {
		$sql = $this->wpdb->prepare( "SELECT * FROM {$this->tabla()} WHERE pieza_id = %d ORDER BY numero_ciclo ASC", $piezaId ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): Borrador => $this->filaABorrador( $fila ), $filas ?? array() );
	}

	public function obtenerUltimo( int $piezaId ): ?Borrador {
		$sql = $this->wpdb->prepare( "SELECT * FROM {$this->tabla()} WHERE pieza_id = %d ORDER BY numero_ciclo DESC LIMIT 1", $piezaId ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$fila = $this->wpdb->get_row( $sql, ARRAY_A );

		return null !== $fila ? $this->filaABorrador( $fila ) : null;
	}

	/**
	 * @param array<string, mixed> $fila
	 */
	private function filaABorrador( array $fila ): Borrador {
		/** @var list<array{punto: string, aprobado: bool, detalle: string}> $anotacionesJson */
		$anotacionesJson = json_decode( (string) $fila['anotaciones_corrector'], true ) ?? array();

		return new Borrador(
			(int) $fila['id'],
			(int) $fila['pieza_id'],
			(int) $fila['numero_ciclo'],
			(string) $fila['contenido'],
			array_map( static fn ( array $a ): AnotacionCorrector => AnotacionCorrector::desdeArray( $a ), $anotacionesJson ),
			(bool) $fila['aprobado_por_corrector'],
			new DateTimeImmutable( (string) $fila['creado_en'] )
		);
	}
}
