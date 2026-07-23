<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Sensores\EstadoTendencia;
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

	public function obtenerRecientes( int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT id, termino, puntuacion_total, detectada_en FROM {$this->tabla()} ORDER BY puntuacion_total DESC, detectada_en DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		if ( ! is_array( $filas ) ) {
			return array();
		}

		return array_map(
			static fn ( array $fila ): array => array(
				'id'              => (int) $fila['id'],
				'termino'         => (string) $fila['termino'],
				'puntuacionTotal' => (float) $fila['puntuacion_total'],
				'detectadaEn'     => (string) $fila['detectada_en'],
			),
			$filas
		);
	}

	public function obtenerParaSala( int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT id, termino, fuente_senal, puntuacion_velocidad, puntuacion_afinidad, puntuacion_total, estado, articulos_relacionados, detectada_en FROM {$this->tabla()} WHERE estado != %s ORDER BY puntuacion_total DESC, detectada_en DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			EstadoTendencia::Ignorada->value,
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		if ( ! is_array( $filas ) ) {
			return array();
		}

		return array_map(
			static function ( array $fila ): array {
				/** @var list<array{titulo: string, url: string, fuente: string}> $articulos */
				$articulos = json_decode( (string) $fila['articulos_relacionados'], true ) ?? array();

				return array(
					'id'                    => (int) $fila['id'],
					'termino'               => (string) $fila['termino'],
					'fuenteSenal'           => (string) $fila['fuente_senal'],
					'velocidad'             => (float) $fila['puntuacion_velocidad'],
					'afinidad'              => (float) $fila['puntuacion_afinidad'],
					'puntuacionTotal'       => (float) $fila['puntuacion_total'],
					'estado'                => EstadoTendencia::tryFrom( (string) $fila['estado'] ) ?? EstadoTendencia::EnPipeline,
					'articulosRelacionados' => $articulos,
					'detectadaEn'           => (string) $fila['detectada_en'],
				);
			},
			$filas
		);
	}

	public function actualizarEstadoTendencia( int $id, EstadoTendencia $estado ): bool {
		$actualizadas = $this->wpdb->update(
			$this->tabla(),
			array( 'estado' => $estado->value ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $actualizadas && $actualizadas > 0;
	}
}
