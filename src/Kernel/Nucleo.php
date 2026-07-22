<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use Pluma\Admin\PantallaSalud;
use wpdb;

/**
 * Raíz de composición del plugin: registra servicios en el Contenedor y
 * arranca los módulos activos en `plugins_loaded`.
 *
 * Cada Etapa futura añade su registro aquí (o en un registrador dedicado que
 * este Núcleo invoque) — jamás llamadas directas entre capas no adyacentes.
 */
final class Nucleo {

	private readonly Contenedor $contenedor;

	public function __construct() {
		$this->contenedor = new Contenedor();
		$this->registrarServicios();
	}

	public function contenedor(): Contenedor {
		return $this->contenedor;
	}

	private function registrarServicios(): void {
		$this->contenedor->registrar( RelojInterface::class, static fn (): RelojSistema => new RelojSistema() );

		$this->contenedor->registrar(
			'wpdb',
			static function (): wpdb {
				global $wpdb;
				assert( $wpdb instanceof wpdb );

				return $wpdb;
			}
		);

		$this->contenedor->registrar(
			DetectorEntorno::class,
			fn ( Contenedor $c ): DetectorEntorno => new DetectorEntorno( $c->obtener( 'wpdb' ) )
		);
	}

	public function arrancar( string $archivoPrincipalPlugin ): void {
		load_plugin_textdomain(
			'pluma-engine',
			false,
			dirname( plugin_basename( $archivoPrincipalPlugin ) ) . '/languages'
		);

		( new PantallaSalud( $this->contenedor->obtener( DetectorEntorno::class ) ) )->registrar();
	}
}
