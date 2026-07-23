<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use RuntimeException;

final class ProveedorLenguajeException extends RuntimeException {

	public function __construct(
		string $mensaje,
		public readonly bool $presupuestoAgotado = false,
		public readonly bool $sinCredenciales = false
	) {
		parent::__construct( $mensaje );
	}
}
