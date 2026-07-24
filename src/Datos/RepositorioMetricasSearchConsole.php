<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Proveedores\FilaAnaliticaSearchConsole;
use wpdb;

/**
 * Único punto del plugin con `$wpdb` para `pluma_metricas_search_console`
 * (CLAUDE.md § Ley de Arquitectura). `url_to_postid()` (WordPress core) es
 * la única forma correcta y verificada de resolver una URL real de
 * `searchAnalytics.query` a un post_id — nunca se parsea la URL a mano.
 */
final class RepositorioMetricasSearchConsole implements RepositorioMetricasSearchConsoleInterface {

	public function __construct(
		private readonly wpdb $wpdb,
		private readonly RepositorioPiezasInterface $piezas,
	) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_metricas_search_console';
	}

	public function guardarLote( array $filas, DateTimeImmutable $ahora ): int {
		$guardadas = 0;

		foreach ( $filas as $fila ) {
			$postId = url_to_postid( $fila->pagina );

			if ( 0 === $postId ) {
				continue;
			}

			$piezaId = $this->piezas->obtenerPorPostId( $postId )?->id;

			$datos    = array(
				'post_id'         => $postId,
				'pieza_id'        => $piezaId,
				'clics'           => $fila->clics,
				'impresiones'     => $fila->impresiones,
				'ctr'             => $fila->ctr,
				'posicion'        => $fila->posicion,
				'sincronizada_en' => $ahora->format( 'Y-m-d H:i:s' ),
			);
			$formatos = array( '%d', '%d', '%d', '%d', '%f', '%f', '%s' );

			$idExistente = $this->idExistente( $postId, $fila->consulta );

			if ( null !== $idExistente ) {
				$this->wpdb->update( $this->tabla(), $datos, array( 'id' => $idExistente ), $formatos, array( '%d' ) );
			} else {
				$this->wpdb->insert( $this->tabla(), $datos + array( 'consulta' => $fila->consulta ), array( ...$formatos, '%s' ) );
			}

			++$guardadas;
		}

		return $guardadas;
	}

	private function idExistente( int $postId, string $consulta ): ?int {
		$sql = $this->wpdb->prepare(
			"SELECT id FROM {$this->tabla()} WHERE post_id = %d AND consulta = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$postId,
			$consulta
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$id = $this->wpdb->get_var( $sql );

		return null !== $id ? (int) $id : null;
	}

	public function obtenerRecientes( int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->tabla()} ORDER BY sincronizada_en DESC, impresiones DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map(
			static fn ( array $fila ): array => array(
				'postId'         => (int) $fila['post_id'],
				'piezaId'        => null !== $fila['pieza_id'] ? (int) $fila['pieza_id'] : null,
				'consulta'       => (string) $fila['consulta'],
				'clics'          => (int) $fila['clics'],
				'impresiones'    => (int) $fila['impresiones'],
				'ctr'            => (float) $fila['ctr'],
				'posicion'       => (float) $fila['posicion'],
				'sincronizadaEn' => ( new DateTimeImmutable( (string) $fila['sincronizada_en'] ) )->format( DATE_ATOM ),
			),
			$filas ?? array()
		);
	}
}
