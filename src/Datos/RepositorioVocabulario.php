<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\TipoVocabulario;
use wpdb;

/**
 * Único punto del plugin con `$wpdb` para `pluma_vocabulario` (CLAUDE.md §
 * Ley de Arquitectura). Ver nota sobre `argument.type` ignorados en
 * `RepositorioPiezas`: mismo motivo (nombre de tabla interno, no de entrada
 * de usuario).
 */
final class RepositorioVocabulario implements RepositorioVocabularioInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_vocabulario';
	}

	public function crear(
		TipoVocabulario $tipo,
		string $nombre,
		string $slug,
		array $alias,
		bool $enCuarentena,
		DateTimeImmutable $ahora
	): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'tipo'           => $tipo->value,
				'nombre'         => $nombre,
				'slug'           => $slug,
				'alias'          => wp_json_encode( $alias ),
				'en_cuarentena'  => $enCuarentena ? 1 : 0,
				'veces_usada'    => 0,
				'creado_en'      => $ahora->format( 'Y-m-d H:i:s' ),
				'actualizado_en' => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function obtenerPorTipoYSlug( TipoVocabulario $tipo, string $slug ): ?EntradaVocabulario {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->tabla()} WHERE tipo = %s AND slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$tipo->value,
			$slug
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$fila = $this->wpdb->get_row( $sql, ARRAY_A );

		return null !== $fila ? $this->filaAEntrada( $fila ) : null;
	}

	public function obtenerPorTipo( TipoVocabulario $tipo ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->tabla()} WHERE tipo = %s ORDER BY veces_usada DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$tipo->value
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): EntradaVocabulario => $this->filaAEntrada( $fila ), $filas ?? array() );
	}

	public function incrementarUso( int $id, DateTimeImmutable $ahora ): bool {
		$sql = $this->wpdb->prepare(
			"UPDATE {$this->tabla()} SET veces_usada = veces_usada + 1, actualizado_en = %s WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$ahora->format( 'Y-m-d H:i:s' ),
			$id
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return false !== $this->wpdb->query( $sql );
	}

	public function salirDeCuarentena( int $id, DateTimeImmutable $ahora ): bool {
		$filasAfectadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'en_cuarentena'  => 0,
				'actualizado_en' => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $filasAfectadas;
	}

	/**
	 * @param array<string, mixed> $fila
	 */
	private function filaAEntrada( array $fila ): EntradaVocabulario {
		/** @var list<string> $alias */
		$alias = json_decode( (string) $fila['alias'], true );

		return new EntradaVocabulario(
			(int) $fila['id'],
			TipoVocabulario::from( (string) $fila['tipo'] ),
			(string) $fila['nombre'],
			(string) $fila['slug'],
			$alias,
			1 === (int) $fila['en_cuarentena'],
			(int) $fila['veces_usada'],
			new DateTimeImmutable( (string) $fila['creado_en'] ),
			new DateTimeImmutable( (string) $fila['actualizado_en'] )
		);
	}
}
