<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use Pluma\Admin\PantallaSalud;
use Pluma\Admin\RestOrquestador;
use Pluma\Datos\CandadoGlobal;
use Pluma\Datos\CandadoGlobalInterface;
use Pluma\Datos\RepositorioAuditoria;
use Pluma\Datos\RepositorioAuditoriaInterface;
use Pluma\Datos\RepositorioBitacora;
use Pluma\Datos\RepositorioBitacoraInterface;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Investigacion\InvestigadorInterface;
use Pluma\Investigacion\InvestigadorMecanico;
use Pluma\Pipeline\Orquestador;
use Pluma\Pipeline\Transicionador;
use Pluma\Proveedores\ProveedorGoogleTrends;
use Pluma\Proveedores\ProveedorTendenciasInterface;
use Pluma\Publicacion\CreadorBorrador;
use Pluma\Publicacion\CreadorBorradorInterface;
use Pluma\Redaccion\RedactorInterface;
use Pluma\Redaccion\RedactorMecanico;
use Pluma\Sensores\SensorGoogleTrends;
use Pluma\Sensores\SensorInterface;
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
		$this->contenedor->registrar( AzarInterface::class, static fn (): AzarSistema => new AzarSistema() );

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

		$this->contenedor->registrar(
			RepositorioPiezasInterface::class,
			fn ( Contenedor $c ): RepositorioPiezas => new RepositorioPiezas( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			RepositorioTendenciasInterface::class,
			fn ( Contenedor $c ): RepositorioTendencias => new RepositorioTendencias( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			RepositorioBitacoraInterface::class,
			fn ( Contenedor $c ): RepositorioBitacora => new RepositorioBitacora( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			RepositorioAuditoriaInterface::class,
			fn ( Contenedor $c ): RepositorioAuditoria => new RepositorioAuditoria( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			CandadoGlobalInterface::class,
			fn ( Contenedor $c ): CandadoGlobal => new CandadoGlobal( $c->obtener( 'wpdb' ) )
		);

		$this->contenedor->registrar(
			Transicionador::class,
			fn ( Contenedor $c ): Transicionador => new Transicionador(
				$c->obtener( RepositorioPiezasInterface::class ),
				$c->obtener( RepositorioAuditoriaInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);

		$this->contenedor->registrar(
			ProveedorTendenciasInterface::class,
			fn ( Contenedor $c ): ProveedorGoogleTrends => new ProveedorGoogleTrends( $c->obtener( RelojInterface::class ) )
		);
		$this->contenedor->registrar(
			SensorInterface::class,
			fn ( Contenedor $c ): SensorGoogleTrends => new SensorGoogleTrends( $c->obtener( ProveedorTendenciasInterface::class ) )
		);
		$this->contenedor->registrar(
			InvestigadorInterface::class,
			fn ( Contenedor $c ): InvestigadorMecanico => new InvestigadorMecanico( $c->obtener( RelojInterface::class ) )
		);
		$this->contenedor->registrar( RedactorInterface::class, static fn (): RedactorMecanico => new RedactorMecanico() );
		$this->contenedor->registrar( CreadorBorradorInterface::class, static fn (): CreadorBorrador => new CreadorBorrador() );

		$this->contenedor->registrar(
			Orquestador::class,
			fn ( Contenedor $c ): Orquestador => new Orquestador(
				$c->obtener( CandadoGlobalInterface::class ),
				$c->obtener( RepositorioBitacoraInterface::class ),
				$c->obtener( RepositorioPiezasInterface::class ),
				$c->obtener( RepositorioTendenciasInterface::class ),
				$c->obtener( Transicionador::class ),
				$c->obtener( SensorInterface::class ),
				$c->obtener( InvestigadorInterface::class ),
				$c->obtener( RedactorInterface::class ),
				$c->obtener( CreadorBorradorInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);

		$this->contenedor->registrar(
			RestOrquestador::class,
			fn ( Contenedor $c ): RestOrquestador => new RestOrquestador( $c->obtener( Orquestador::class ) )
		);
	}

	public function arrancar( string $archivoPrincipalPlugin ): void {
		load_plugin_textdomain(
			'pluma-engine',
			false,
			dirname( plugin_basename( $archivoPrincipalPlugin ) ) . '/languages'
		);

		( new PantallaSalud( $this->contenedor->obtener( DetectorEntorno::class ) ) )->registrar();
		$this->contenedor->obtener( RestOrquestador::class )->registrar();
	}
}
