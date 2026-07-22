<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use Pluma\Kernel\Excepciones\ServicioNoRegistradoException;

/**
 * Contenedor de inyección de dependencias del Kernel.
 *
 * Deliberadamente propio y sin autowiring: el registro explícito de cada
 * servicio es auditable en una sola pasada de lectura y no exige prefijar
 * ninguna dependencia externa vía PHP-Scoper (GOVERNANCE §5.2, LG Elegance —
 * la solución mínima que satisface los axiomas de la Etapa 0).
 */
final class Contenedor {

	/** @var array<string, callable(self): mixed> */
	private array $fabricas = array();

	/** @var array<string, mixed> */
	private array $instancias = array();

	/**
	 * @param callable(self): mixed $fabrica
	 */
	public function registrar( string $id, callable $fabrica ): void {
		$this->fabricas[ $id ] = $fabrica;
		unset( $this->instancias[ $id ] );
	}

	public function tiene( string $id ): bool {
		return isset( $this->fabricas[ $id ] );
	}

	/**
	 * @throws ServicioNoRegistradoException
	 */
	public function obtener( string $id ): mixed {
		if ( array_key_exists( $id, $this->instancias ) ) {
			return $this->instancias[ $id ];
		}

		if ( ! isset( $this->fabricas[ $id ] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
			throw new ServicioNoRegistradoException( $id );
		}

		$instancia               = ( $this->fabricas[ $id ] )( $this );
		$this->instancias[ $id ] = $instancia;

		return $instancia;
	}
}
