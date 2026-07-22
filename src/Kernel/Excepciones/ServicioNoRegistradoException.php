<?php

declare(strict_types=1);

namespace Pluma\Kernel\Excepciones;

use RuntimeException;

/**
 * Se lanza cuando el Contenedor recibe una petición de un servicio que nadie registró.
 */
final class ServicioNoRegistradoException extends RuntimeException {

	public function __construct( string $idServicio ) {
		parent::__construct( sprintf( 'PLUMA: el servicio "%s" no está registrado en el Contenedor.', $idServicio ) );
	}
}
