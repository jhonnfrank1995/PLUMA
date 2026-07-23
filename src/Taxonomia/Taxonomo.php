<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

use Pluma\Investigacion\Expediente;

/**
 * Único punto de entrada de `Pluma\Taxonomia` (Libro Cap. 7): orquesta la
 * asignación de categoría (nunca crea) y el etiquetado (crea con umbral y
 * cuarentena) en una sola llamada.
 */
final class Taxonomo {

	public function __construct(
		private readonly AsignadorCategoria $asignadorCategoria,
		private readonly GestorEtiquetas $gestorEtiquetas,
	) {
	}

	public function clasificar( Expediente $expediente, string $tema, string $tesis ): ResultadoTaxonomia {
		return new ResultadoTaxonomia(
			$this->asignadorCategoria->asignar( $tema ),
			$this->gestorEtiquetas->asignar( $expediente, $tesis )
		);
	}
}
