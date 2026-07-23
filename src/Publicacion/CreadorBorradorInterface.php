<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

use Pluma\Redaccion\BorradorMecanico;

interface CreadorBorradorInterface {

	/**
	 * @throws CreacionBorradorException
	 */
	public function crear( BorradorMecanico $borrador ): int;
}
