<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

use Pluma\Datos\RepositorioVocabularioInterface;

/**
 * Asignación de categoría (Libro Cap. 7.1): "el Taxónomo jamás crea
 * categorías: solo asigna". Las categorías son arquitectura fija del sitio
 * (5-8, definidas por el editor) — si ninguna alcanza el umbral de
 * similitud contra el tema de la pieza, se devuelve `null` y la pieza queda
 * sin categoría para revisión humana; nunca se inventa una.
 */
final class AsignadorCategoria {

	public function __construct(
		private readonly ReconciliadorVocabulario $reconciliador,
		private readonly RepositorioVocabularioInterface $repositorio,
	) {
	}

	public function asignar( string $tema ): ?string {
		$categorias = $this->repositorio->obtenerPorTipo( TipoVocabulario::Categoria );
		$encontrada = $this->reconciliador->reconciliar( $tema, $categorias );

		return $encontrada?->nombre;
	}
}
