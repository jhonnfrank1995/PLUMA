<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Una fila cruda de `searchAnalytics.query` (Libro Cap. 6.4), agregada por
 * página+consulta. La interpretación (resolver a qué Pieza pertenece la
 * página, decidir qué hacer con el dato) vive en `Pluma\Datos`, no aquí.
 */
final readonly class FilaAnaliticaSearchConsole {

	public function __construct(
		public string $pagina,
		public string $consulta,
		public int $clics,
		public int $impresiones,
		public float $ctr,
		public float $posicion,
	) {
	}
}
