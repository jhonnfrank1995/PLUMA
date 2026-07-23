<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Investigacion\Expediente;
use Pluma\Kernel\RelojInterface;

/**
 * Algoritmo de etiquetado (Libro Cap. 7.2): reconcilia cada entidad central
 * contra el vocabulario existente; si no hay equivalente, crea una etiqueta
 * nueva EN CUARENTENA (punto 3 — "solo los archivos con contenido real
 * merecen estar en Google"). Regla del producto: 3-6 etiquetas por pieza;
 * menos de 3 no es un error (el expediente puede ser pobre en entidades),
 * más de 6 se recorta a las más centrales.
 */
final class GestorEtiquetas {

	private const MAXIMO_ETIQUETAS = 6;

	public function __construct(
		private readonly ExtractorEntidades $extractor,
		private readonly ReconciliadorVocabulario $reconciliador,
		private readonly RepositorioVocabularioInterface $repositorio,
		private readonly RelojInterface $reloj,
	) {
	}

	/**
	 * @return list<EtiquetaAsignada>
	 */
	public function asignar( Expediente $expediente, string $tesis ): array {
		$entidades  = array_slice( $this->extractor->extraer( $expediente, $tesis ), 0, self::MAXIMO_ETIQUETAS );
		$existentes = $this->repositorio->obtenerPorTipo( TipoVocabulario::Etiqueta );

		$asignadas = array();

		foreach ( $entidades as $nombre ) {
			$asignadas[] = $this->reconciliarOCrear( $nombre, $existentes );
		}

		return $asignadas;
	}

	/**
	 * @param list<EntradaVocabulario> $existentes
	 */
	private function reconciliarOCrear( string $nombre, array $existentes ): EtiquetaAsignada {
		$encontrada = $this->reconciliador->reconciliar( $nombre, $existentes );

		if ( null !== $encontrada ) {
			$this->repositorio->incrementarUso( $encontrada->id, $this->reloj->ahora() );

			return new EtiquetaAsignada( $encontrada->id, $encontrada->nombre, false, $encontrada->enCuarentena );
		}

		$id = $this->repositorio->crear( TipoVocabulario::Etiqueta, $nombre, sanitize_title( $nombre ), array(), true, $this->reloj->ahora() );

		return new EtiquetaAsignada( $id, $nombre, true, true );
	}
}
