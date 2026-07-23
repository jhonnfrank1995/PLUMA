<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Datos\RepositorioPiezasInterface;

/**
 * Auditoría de canibalización (Libro Cap. 6.3): "el motor verifica si el
 * sitio ya tiene una pieza posicionando por la misma keyword; si la hay,
 * propone convertir la nueva en actualización de la existente o diferenciar
 * el enfoque". Esta clase solo detecta — la decisión de actualizar vs.
 * diferenciar queda para el editor humano en la Sala de Revisión (Etapa 3, H3).
 */
final class AuditorCanibalizacion {

	public function __construct( private readonly RepositorioPiezasInterface $repositorioPiezas ) {
	}

	public function hayCanibalizacion( string $keywordPrincipal, int $piezaActualId ): bool {
		return $this->repositorioPiezas->existePiezaPublicadaConKeyword( $keywordPrincipal, $piezaActualId );
	}
}
