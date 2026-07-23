<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

/**
 * Reconciliación contra el vocabulario existente (Libro Cap. 7.2, punto 2):
 * "cada entidad se compara con las etiquetas del sitio por coincidencia
 * exacta, alias conocidos... y similitud. Si existe, se reutiliza —
 * siempre." `sanitize_title()` (WordPress core) normaliza para la
 * coincidencia exacta y de alias; `similar_text()` (PHP core) aproxima la
 * similitud — ninguna de las dos es NLP real, ambas son funciones nativas
 * verificadas, no heurísticas inventadas desde cero.
 */
final class ReconciliadorVocabulario {

	public const UMBRAL_SIMILITUD_PORCENTAJE = 85.0;

	/**
	 * @param list<EntradaVocabulario> $candidatos
	 */
	public function reconciliar( string $nombre, array $candidatos ): ?EntradaVocabulario {
		$slugCandidato = sanitize_title( $nombre );

		foreach ( $candidatos as $entrada ) {
			if ( $slugCandidato === $entrada->slug ) {
				return $entrada;
			}
		}

		foreach ( $candidatos as $entrada ) {
			foreach ( $entrada->alias as $alias ) {
				if ( sanitize_title( $alias ) === $slugCandidato ) {
					return $entrada;
				}
			}
		}

		foreach ( $candidatos as $entrada ) {
			if ( $this->similitud( $nombre, $entrada->nombre ) >= self::UMBRAL_SIMILITUD_PORCENTAJE ) {
				return $entrada;
			}
		}

		return null;
	}

	/**
	 * Expuesto (Estudio SEO y Taxonomía, Libro Cap. 10.2: "propuestas de
	 * fusión") para que el panel detecte pares de etiquetas casi-duplicadas
	 * que la reconciliación automática no fusionó (porque no comparten slug
	 * ni alias exacto) — misma función y mismo umbral que `reconciliar()`,
	 * sin duplicar la lógica de similitud.
	 */
	public function similitud( string $a, string $b ): float {
		similar_text( mb_strtolower( $a ), mb_strtolower( $b ), $porcentaje );

		return $porcentaje;
	}
}
