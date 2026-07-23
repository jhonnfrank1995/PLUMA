<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

/**
 * Enrutamiento por coste (CLAUDE.md § Contrato del Proveedor de Lenguaje,
 * Libro Cap. 12.3): clasificar con modelo económico, redactar con el mejor.
 * Editable en configuración — jamás hardcodeado en la lógica editorial.
 *
 * Slugs verificados contra el catálogo real de OpenRouter
 * (`GET https://openrouter.ai/api/v1/models`) al escribir este archivo.
 */
final class EnrutadorModelos {

	public const OPCION_MODELO_ECONOMICO = 'pluma_modelo_economico';
	public const OPCION_MODELO_PREMIUM   = 'pluma_modelo_premium';

	private const MODELO_ECONOMICO_DEFECTO = 'anthropic/claude-haiku-4.5';
	private const MODELO_PREMIUM_DEFECTO   = 'anthropic/claude-sonnet-5';

	public function modeloPara( PropositoLenguaje $proposito ): string {
		$opcion  = $proposito->esPremium() ? self::OPCION_MODELO_PREMIUM : self::OPCION_MODELO_ECONOMICO;
		$defecto = $proposito->esPremium() ? self::MODELO_PREMIUM_DEFECTO : self::MODELO_ECONOMICO_DEFECTO;

		$modelo = get_option( $opcion, $defecto );

		return is_string( $modelo ) && '' !== $modelo ? $modelo : $defecto;
	}
}
