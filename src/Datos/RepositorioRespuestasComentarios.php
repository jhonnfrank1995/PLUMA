<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Redaccion\RespuestaComentario;
use wpdb;

/**
 * Único punto del plugin con `$wpdb` para `pluma_respuestas_comentarios`
 * (CLAUDE.md § Ley de Arquitectura).
 */
final class RepositorioRespuestasComentarios implements RepositorioRespuestasComentariosInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_respuestas_comentarios';
	}

	public function yaProcesado( int $comentarioId ): bool {
		$sql = $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->tabla()} WHERE comentario_id = %d", $comentarioId ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return ( (int) $this->wpdb->get_var( $sql ) ) > 0;
	}

	public function registrar(
		int $piezaId,
		int $comentarioId,
		?int $periodistaId,
		?string $borrador,
		EstadoRespuestaComentario $estado,
		DateTimeImmutable $ahora
	): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'pieza_id'      => $piezaId,
				'comentario_id' => $comentarioId,
				'periodista_id' => $periodistaId,
				'borrador'      => $borrador,
				'estado'        => $estado->value,
				'creada_en'     => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function obtenerPorId( int $id ): ?RespuestaComentario {
		$sql = $this->wpdb->prepare( "SELECT * FROM {$this->tabla()} WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$fila = $this->wpdb->get_row( $sql, ARRAY_A );

		return null !== $fila ? $this->filaARespuesta( $fila ) : null;
	}

	public function obtenerPendientes( int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->tabla()} WHERE estado = %s ORDER BY creada_en ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			EstadoRespuestaComentario::PendienteAprobacion->value,
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): RespuestaComentario => $this->filaARespuesta( $fila ), $filas ?? array() );
	}

	public function contarPendientes(): int {
		$sql = $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->tabla()} WHERE estado = %s", EstadoRespuestaComentario::PendienteAprobacion->value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return (int) $this->wpdb->get_var( $sql );
	}

	public function contarCreadosEntre( DateTimeImmutable $desde, DateTimeImmutable $hasta ): int {
		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tabla()} WHERE creada_en BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$desde->format( 'Y-m-d H:i:s' ),
			$hasta->format( 'Y-m-d H:i:s' )
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return (int) $this->wpdb->get_var( $sql );
	}

	public function contarPorEstadoResueltoEntre( EstadoRespuestaComentario $estado, DateTimeImmutable $desde, DateTimeImmutable $hasta ): int {
		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tabla()} WHERE estado = %s AND resuelta_en BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$estado->value,
			$desde->format( 'Y-m-d H:i:s' ),
			$hasta->format( 'Y-m-d H:i:s' )
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return (int) $this->wpdb->get_var( $sql );
	}

	public function marcarResuelta(
		int $id,
		EstadoRespuestaComentario $nuevoEstado,
		?int $comentarioRespuestaId,
		DateTimeImmutable $ahora
	): bool {
		$actualizadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'estado'                  => $nuevoEstado->value,
				'comentario_respuesta_id' => $comentarioRespuestaId,
				'resuelta_en'             => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		return false !== $actualizadas && $actualizadas > 0;
	}

	/**
	 * @param array<string, mixed> $fila
	 */
	private function filaARespuesta( array $fila ): RespuestaComentario {
		return new RespuestaComentario(
			(int) $fila['id'],
			(int) $fila['pieza_id'],
			(int) $fila['comentario_id'],
			null !== $fila['periodista_id'] ? (int) $fila['periodista_id'] : null,
			null !== $fila['borrador'] ? (string) $fila['borrador'] : null,
			EstadoRespuestaComentario::from( (string) $fila['estado'] ),
			null !== $fila['comentario_respuesta_id'] ? (int) $fila['comentario_respuesta_id'] : null,
			new DateTimeImmutable( (string) $fila['creada_en'] ),
			null !== $fila['resuelta_en'] ? new DateTimeImmutable( (string) $fila['resuelta_en'] ) : null
		);
	}
}
