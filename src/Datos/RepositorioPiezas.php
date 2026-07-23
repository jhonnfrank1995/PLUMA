<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Pipeline\EstadoPieza;
use Pluma\Pipeline\Pieza;
use Pluma\Redaccion\FichaDecisionEditorial;
use wpdb;

/**
 * Único punto del plugin con `$wpdb` para `pluma_piezas` (CLAUDE.md § Ley de
 * Arquitectura). Las consultas por estado están indexadas (estado,
 * actualizada_en) porque el motor siempre pregunta "dame N piezas en estado
 * X por prioridad".
 *
 * Nota sobre los `argument.type` ignorados: `phpstan-wordpress` exige que el
 * primer argumento de `$wpdb->prepare()` sea un "literal-string" para
 * prevenir inyección SQL. El nombre de tabla que interpolamos viene siempre
 * de `$this->tabla()` (interno, nunca de entrada de usuario) — por eso se
 * ignora puntualmente en vez de perseguir un literal imposible con un
 * prefijo de tabla dinámico.
 */
final class RepositorioPiezas implements RepositorioPiezasInterface {

	public function __construct( private readonly wpdb $wpdb ) {
	}

	private function tabla(): string {
		return $this->wpdb->prefix . 'pluma_piezas';
	}

	public function crear( int $tendenciaId, DateTimeImmutable $ahora ): int {
		$this->wpdb->insert(
			$this->tabla(),
			array(
				'tendencia_id'   => $tendenciaId,
				'estado'         => EstadoPieza::Detectada->value,
				'expediente'     => null,
				'post_id'        => null,
				'creada_en'      => $ahora->format( 'Y-m-d H:i:s' ),
				'actualizada_en' => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $this->wpdb->insert_id;
	}

	public function obtenerPorId( int $id ): ?Pieza {
		$sql = $this->wpdb->prepare( "SELECT * FROM {$this->tabla()} WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$fila = $this->wpdb->get_row( $sql, ARRAY_A );

		return null !== $fila ? $this->filaAPieza( $fila ) : null;
	}

	public function obtenerPorEstado( EstadoPieza $estado, int $limite ): array {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->tabla()} WHERE estado = %s ORDER BY actualizada_en ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$estado->value,
			$limite
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filas = $this->wpdb->get_results( $sql, ARRAY_A );

		return array_map( fn ( array $fila ): Pieza => $this->filaAPieza( $fila ), $filas ?? array() );
	}

	public function actualizarEstado(
		int $id,
		EstadoPieza $estadoEsperado,
		EstadoPieza $nuevoEstado,
		DateTimeImmutable $ahora
	): bool {
		$sql = $this->wpdb->prepare(
			"UPDATE {$this->tabla()} SET estado = %s, actualizada_en = %s WHERE id = %d AND estado = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$nuevoEstado->value,
			$ahora->format( 'Y-m-d H:i:s' ),
			$id,
			$estadoEsperado->value
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		$filasAfectadas = $this->wpdb->query( $sql );

		return 1 === $filasAfectadas;
	}

	public function actualizarExpediente( int $id, Expediente $expediente, DateTimeImmutable $ahora ): bool {
		$filasAfectadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'expediente'     => wp_json_encode( $expediente->aArray() ),
				'actualizada_en' => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $filasAfectadas;
	}

	public function actualizarPostId( int $id, int $postId, DateTimeImmutable $ahora ): bool {
		$filasAfectadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'post_id'        => $postId,
				'actualizada_en' => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		return false !== $filasAfectadas;
	}

	public function asignarPeriodista( int $id, int $periodistaId, int $periodistaVersionId, DateTimeImmutable $ahora ): bool {
		$filasAfectadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'periodista_id'         => $periodistaId,
				'periodista_version_id' => $periodistaVersionId,
				'actualizada_en'        => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%d', '%d', '%s' ),
			array( '%d' )
		);

		return false !== $filasAfectadas;
	}

	public function actualizarFichaDecisionEditorial( int $id, FichaDecisionEditorial $ficha, DateTimeImmutable $ahora ): bool {
		$filasAfectadas = $this->wpdb->update(
			$this->tabla(),
			array(
				'ficha_decision_editorial' => wp_json_encode( $ficha->aArray() ),
				'actualizada_en'           => $ahora->format( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return false !== $filasAfectadas;
	}

	public function contarAsignadasDesde( int $periodistaId, DateTimeImmutable $desde ): int {
		$sql = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->tabla()} WHERE periodista_id = %d AND creada_en >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tabla interna. @phpstan-ignore-line argument.type
			$periodistaId,
			$desde->format( 'Y-m-d H:i:s' )
		);
		assert( null !== $sql );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql ya se construyó con $wpdb->prepare() arriba.
		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * @param array<string, mixed> $fila
	 */
	private function filaAPieza( array $fila ): Pieza {
		$expedienteJson = $fila['expediente'] ?? null;
		$expediente     = null;

		if ( is_string( $expedienteJson ) && '' !== $expedienteJson ) {
			/** @var array{tendenciaOrigen: string, hechos: list<array{extracto: string, url: string, fecha: string, nivel: string}>} $datos */
			$datos      = json_decode( $expedienteJson, true );
			$expediente = Expediente::desdeArray( $datos );
		}

		$fichaJson = $fila['ficha_decision_editorial'] ?? null;
		$ficha     = null;

		if ( is_string( $fichaJson ) && '' !== $fichaJson ) {
			/** @var array{periodistaId: int, periodistaVersionId: int, clasificacion: array{tema: string, gravedad: int, polaridad: string, novedad: string, potencialConversacional: int, tipoNoticia: string}, candidatosTesis: list<array{tesis: string, puntuacionOriginalidad: float, puntuacionCompatibilidadLinea: float, puntuacionSustento: float, puntuacionConversacional: float}>, indiceTesisElegida: int, tonoDominante: string, tonoApoyo: string, esqueleto: array{gancho: string, hechosEsencialesConAtribucion: string, movimientosArgumentales: list<string>, contraargumentoReconocido: string, remate: string}, creadaEn: string} $datosFicha */
			$datosFicha = json_decode( $fichaJson, true );
			$ficha      = FichaDecisionEditorial::desdeArray( $datosFicha );
		}

		return new Pieza(
			(int) $fila['id'],
			(int) $fila['tendencia_id'],
			EstadoPieza::from( (string) $fila['estado'] ),
			$expediente,
			null !== $fila['post_id'] ? (int) $fila['post_id'] : null,
			new DateTimeImmutable( (string) $fila['creada_en'] ),
			new DateTimeImmutable( (string) $fila['actualizada_en'] ),
			null !== ( $fila['periodista_id'] ?? null ) ? (int) $fila['periodista_id'] : null,
			null !== ( $fila['periodista_version_id'] ?? null ) ? (int) $fila['periodista_version_id'] : null,
			$ficha
		);
	}
}
