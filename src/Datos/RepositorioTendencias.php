<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Sensores\TendenciaDetectada;
use wpdb;

final class RepositorioTendencias implements RepositorioTendenciasInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_tendencias';
	}

	public function existePorTermino( string $termino, string $fuenteSenal ): bool {
		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tabla()} WHERE termino = %s AND fuente_senal = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			mb_strtolower( trim( $termino ) ),
			$fuenteSenal
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$total = $this->wpdb->get_var( $sql );

		return null !== $total && (int) $total > 0;
	}

	public function guardar( TendenciaDetectada $tendencia, DateTimeImmutable $ahora ): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'termino'                => mb_strtolower( trim( $tendencia->termino ) ),
				'fuente_senal'           => $tendencia->fuenteSenal,
				'puntuacion_velocidad'   => $tendencia->puntuacion->velocidad,
				'puntuacion_afinidad'    => $tendencia->puntuacion->afinidad,
				'puntuacion_total'       => $tendencia->puntuacion->total,
				'articulos_relacionados' => wp_json_encode( $tendencia->articulosRelacionados ),
				'detectada_en'           => $tendencia->detectadaEn->format( 'Y-m-d H:i:s' ),
				'creada_en'              => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function obtenerPorId( int $id ): ?array {
		$sql = $this->wpdb->prepare( "SELECT termino, articulos_relacionados FROM {$this->tabla()} WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$fila = $this->wpdb->get_row( $sql, ARRAY_A );

		if ( null === $fila ) {
			return null;
		}

		/** @var list<array{titulo: string, url: string, fuente: string}> $articulos */
		$articulos = json_decode( (string) $fila['articulos_relacionados'], true ) ?? array();

		return array(
			'termino'               => (string) $fila['termino'],
			'articulosRelacionados' => $articulos,
		);
	}
}
