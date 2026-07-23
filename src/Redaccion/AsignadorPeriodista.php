<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Paso 2 del Algoritmo de Decisión Editorial (Libro Cap. 5.5): puntúa a cada
 * periodista del banco contra la pieza y devuelve el de mayor puntuación.
 *
 * Lógica pura, sin `$wpdb` ni proveedor de lenguaje (CLAUDE.md § Ley de
 * Arquitectura): `DecisionEditorial` reúne los datos de carga e historial vía
 * los repositorios y se los pasa ya resueltos, para que este puntuador sea
 * determinista y trivial de testear.
 *
 * Deuda documentada (docs/deuda.md): la afinidad de línea editorial usa un
 * heurístico léxico simple (solapamiento de palabras), no comprensión
 * semántica real — suficiente para el criterio de salida de la Etapa 2, pero
 * candidato a mejora futura (embeddings o puntuación vía proveedor de lenguaje).
 */
final class AsignadorPeriodista {

	private const PESO_DOMINIO               = 0.40;
	private const PESO_AFINIDAD              = 0.25;
	private const PESO_HISTORIAL             = 0.15;
	private const PESO_BALANCE_CARGA         = 0.20;
	private const PENALIZACION_POR_PIEZA_HOY = 25.0;

	/**
	 * @param list<Periodista> $candidatos periodistas activos del banco
	 * @param array<int, int> $piezasAsignadasHoyPorPeriodista periodistaId => piezas ya asignadas hoy (balance de carga)
	 * @param array<int, bool> $tieneHistorialPorPeriodista periodistaId => ¿ya cubrió este tema antes? (historial de cobertura)
	 *
	 * @throws DecisionEditorialException si `$candidatos` está vacío.
	 */
	public function asignar(
		array $candidatos,
		ClasificacionNoticia $clasificacion,
		array $piezasAsignadasHoyPorPeriodista,
		array $tieneHistorialPorPeriodista
	): Periodista {
		if ( array() === $candidatos ) {
			throw new DecisionEditorialException( 'No hay periodistas activos en el banco para asignar esta pieza.' );
		}

		$mejor           = $candidatos[0];
		$mejorPuntuacion = -INF;

		foreach ( $candidatos as $periodista ) {
			$puntuacion = $this->puntuar(
				$periodista,
				$clasificacion,
				$piezasAsignadasHoyPorPeriodista[ $periodista->id ] ?? 0,
				$tieneHistorialPorPeriodista[ $periodista->id ] ?? false
			);

			if ( $puntuacion > $mejorPuntuacion ) {
				$mejorPuntuacion = $puntuacion;
				$mejor           = $periodista;
			}
		}

		return $mejor;
	}

	private function puntuar( Periodista $periodista, ClasificacionNoticia $clasificacion, int $piezasHoy, bool $tieneHistorial ): float {
		$dominio      = ( $periodista->dominioDe( $clasificacion->tema ) / 5.0 ) * 100.0;
		$afinidad     = $this->afinidadLineaEditorial( $periodista->conductaActual->reglas->lineaEditorial, $clasificacion );
		$historial    = $tieneHistorial ? 100.0 : 0.0;
		$balanceCarga = max( 0.0, 100.0 - $piezasHoy * self::PENALIZACION_POR_PIEZA_HOY );

		return self::PESO_DOMINIO * $dominio
			+ self::PESO_AFINIDAD * $afinidad
			+ self::PESO_HISTORIAL * $historial
			+ self::PESO_BALANCE_CARGA * $balanceCarga;
	}

	private function afinidadLineaEditorial( string $lineaEditorial, ClasificacionNoticia $clasificacion ): float {
		$textoNoticia = mb_strtolower( $clasificacion->tema . ' ' . $clasificacion->polaridad );

		$palabrasLinea = array_filter(
			explode( ' ', (string) preg_replace( '/[^\p{L}\s]+/u', ' ', mb_strtolower( $lineaEditorial ) ) ),
			static fn ( string $palabra ): bool => mb_strlen( $palabra ) >= 4
		);

		if ( array() === $palabrasLinea ) {
			return 0.0;
		}

		$coincidencias = 0;

		foreach ( $palabrasLinea as $palabra ) {
			if ( str_contains( $textoNoticia, $palabra ) ) {
				++$coincidencias;
			}
		}

		return ( $coincidencias / count( $palabrasLinea ) ) * 100.0;
	}
}
