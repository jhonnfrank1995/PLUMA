<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Único punto de contacto con modelos de IA (CLAUDE.md § Contrato del
 * Proveedor de Lenguaje). La lógica editorial NO conoce qué proveedor hay
 * detrás; cambiar de proveedor no toca `Pluma\Redaccion`.
 */
interface LenguajeInterface {

	/**
	 * @throws ProveedorLenguajeException fallo de red/HTTP/formato, respuesta
	 *                                    truncada o presupuesto agotado.
	 */
	public function completar( PeticionLenguaje $peticion ): RespuestaLenguaje;
}
