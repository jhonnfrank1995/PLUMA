<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Petición inmutable al proveedor de lenguaje (contrato-lenguaje.md).
 *
 * `directrices` = instrucciones del sistema (identidad+estilo del periodista,
 * con versión). `material` = contenido del expediente, SIEMPRE tratado como
 * datos: el proveedor lo envuelve con la delimitación del
 * NeutralizadorMaterial antes de enviarlo (GOVERNANCE §3.4).
 */
final readonly class PeticionLenguaje {

	public function __construct(
		public PropositoLenguaje $proposito,
		public string $directrices,
		public string $material,
		public int $maxTokens,
	) {
	}
}
