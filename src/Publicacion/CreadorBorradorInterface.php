<?php

declare(strict_types=1);

namespace Pluma\Publicacion;

interface CreadorBorradorInterface {

	/**
	 * @throws CreacionBorradorException
	 */
	public function crear( string $titulo, string $cuerpoHtml ): int;
}
