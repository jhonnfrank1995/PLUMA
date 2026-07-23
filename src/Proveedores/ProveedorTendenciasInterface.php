<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Contrato con una API/feed externo de tendencias. Único lugar del plugin
 * con permiso de hacer HTTP saliente para esta señal (CLAUDE.md § Ley de
 * Arquitectura). `Pluma\Sensores` consume esto, jamás un SDK/HTTP directo.
 */
interface ProveedorTendenciasInterface {

	/**
	 * @return list<TendenciaCruda>
	 *
	 * @throws ProveedorTendenciasException si la fuente no responde o el
	 *                                       formato no puede interpretarse.
	 */
	public function obtenerTendenciasCrudas(): array;
}
