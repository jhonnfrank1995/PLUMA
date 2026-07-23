<?php

declare(strict_types=1);

namespace Pluma\Kernel;

use Pluma\Admin\PantallaSalud;
use Pluma\Admin\RestBancoPeriodistas;
use Pluma\Admin\RestOrquestador;
use Pluma\Datos\CandadoGlobal;
use Pluma\Datos\CandadoGlobalInterface;
use Pluma\Datos\RepositorioAuditoria;
use Pluma\Datos\RepositorioAuditoriaInterface;
use Pluma\Datos\RepositorioBitacora;
use Pluma\Datos\RepositorioBitacoraInterface;
use Pluma\Datos\RepositorioBorradores;
use Pluma\Datos\RepositorioBorradoresInterface;
use Pluma\Datos\RepositorioMemoriaEditorial;
use Pluma\Datos\RepositorioMemoriaEditorialInterface;
use Pluma\Datos\RepositorioPeriodistas;
use Pluma\Datos\RepositorioPeriodistasInterface;
use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Datos\RepositorioTendenciasInterface;
use Pluma\Investigacion\InvestigadorInterface;
use Pluma\Investigacion\InvestigadorMecanico;
use Pluma\Pipeline\Orquestador;
use Pluma\Pipeline\Transicionador;
use Pluma\Proveedores\EnrutadorModelos;
use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PresupuestoLenguaje;
use Pluma\Proveedores\ProveedorGoogleTrends;
use Pluma\Proveedores\ProveedorOpenRouter;
use Pluma\Proveedores\ProveedorTendenciasInterface;
use Pluma\Publicacion\CreadorBorrador;
use Pluma\Publicacion\CreadorBorradorInterface;
use Pluma\Redaccion\AsignadorPeriodista;
use Pluma\Redaccion\AvisoTransparenciaIa;
use Pluma\Redaccion\ClasificadorNoticia;
use Pluma\Redaccion\CompiladorDirectrices;
use Pluma\Redaccion\CorrectorInterno;
use Pluma\Redaccion\DecisionEditorial;
use Pluma\Redaccion\ExportadorBancoPeriodistas;
use Pluma\Redaccion\GeneradorBloqueEditor;
use Pluma\Redaccion\GeneradorEsqueleto;
use Pluma\Redaccion\ImportadorBancoPeriodistas;
use Pluma\Redaccion\RedactorConFallbackMecanico;
use Pluma\Redaccion\RedactorInterface;
use Pluma\Redaccion\RedactorMecanico;
use Pluma\Redaccion\RedactorSintetico;
use Pluma\Redaccion\SelectorAngulo;
use Pluma\Redaccion\VerificadorNGramas;
use Pluma\Redaccion\VerificadorVoz;
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
			RepositorioPeriodistasInterface::class,
			fn ( Contenedor $c ): RepositorioPeriodistas => new RepositorioPeriodistas( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			RepositorioMemoriaEditorialInterface::class,
			fn ( Contenedor $c ): RepositorioMemoriaEditorial => new RepositorioMemoriaEditorial( $c->obtener( 'wpdb' ) )
		);
		$this->contenedor->registrar(
			RepositorioBorradoresInterface::class,
			fn ( Contenedor $c ): RepositorioBorradores => new RepositorioBorradores( $c->obtener( 'wpdb' ) )
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
		$this->contenedor->registrar( CreadorBorradorInterface::class, static fn (): CreadorBorrador => new CreadorBorrador() );

		$this->contenedor->registrar( EnrutadorModelos::class, static fn (): EnrutadorModelos => new EnrutadorModelos() );
		$this->contenedor->registrar(
			PresupuestoLenguaje::class,
			fn ( Contenedor $c ): PresupuestoLenguaje => new PresupuestoLenguaje( $c->obtener( RelojInterface::class ) )
		);
		$this->contenedor->registrar(
			LenguajeInterface::class,
			fn ( Contenedor $c ): ProveedorOpenRouter => new ProveedorOpenRouter(
				$c->obtener( EnrutadorModelos::class ),
				$c->obtener( PresupuestoLenguaje::class ),
				$c->obtener( RelojInterface::class )
			)
		);

		$this->contenedor->registrar(
			ClasificadorNoticia::class,
			fn ( Contenedor $c ): ClasificadorNoticia => new ClasificadorNoticia( $c->obtener( LenguajeInterface::class ) )
		);
		$this->contenedor->registrar( AsignadorPeriodista::class, static fn (): AsignadorPeriodista => new AsignadorPeriodista() );
		$this->contenedor->registrar(
			SelectorAngulo::class,
			fn ( Contenedor $c ): SelectorAngulo => new SelectorAngulo( $c->obtener( LenguajeInterface::class ) )
		);
		$this->contenedor->registrar(
			GeneradorEsqueleto::class,
			fn ( Contenedor $c ): GeneradorEsqueleto => new GeneradorEsqueleto( $c->obtener( LenguajeInterface::class ) )
		);
		$this->contenedor->registrar(
			DecisionEditorial::class,
			fn ( Contenedor $c ): DecisionEditorial => new DecisionEditorial(
				$c->obtener( ClasificadorNoticia::class ),
				$c->obtener( AsignadorPeriodista::class ),
				$c->obtener( SelectorAngulo::class ),
				$c->obtener( GeneradorEsqueleto::class ),
				$c->obtener( RepositorioPeriodistasInterface::class ),
				$c->obtener( RepositorioMemoriaEditorialInterface::class ),
				$c->obtener( RepositorioPiezasInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);

		$this->contenedor->registrar( CompiladorDirectrices::class, static fn (): CompiladorDirectrices => new CompiladorDirectrices() );
		$this->contenedor->registrar( VerificadorVoz::class, static fn (): VerificadorVoz => new VerificadorVoz() );
		$this->contenedor->registrar( VerificadorNGramas::class, static fn (): VerificadorNGramas => new VerificadorNGramas() );
		$this->contenedor->registrar(
			CorrectorInterno::class,
			fn ( Contenedor $c ): CorrectorInterno => new CorrectorInterno(
				$c->obtener( LenguajeInterface::class ),
				$c->obtener( VerificadorVoz::class ),
				$c->obtener( VerificadorNGramas::class )
			)
		);
		$this->contenedor->registrar(
			GeneradorBloqueEditor::class,
			fn ( Contenedor $c ): GeneradorBloqueEditor => new GeneradorBloqueEditor( $c->obtener( LenguajeInterface::class ) )
		);
		$this->contenedor->registrar( AvisoTransparenciaIa::class, static fn (): AvisoTransparenciaIa => new AvisoTransparenciaIa() );
		$this->contenedor->registrar(
			RedactorSintetico::class,
			fn ( Contenedor $c ): RedactorSintetico => new RedactorSintetico(
				$c->obtener( LenguajeInterface::class ),
				$c->obtener( CompiladorDirectrices::class ),
				$c->obtener( CorrectorInterno::class ),
				$c->obtener( GeneradorBloqueEditor::class ),
				$c->obtener( AvisoTransparenciaIa::class ),
				$c->obtener( RepositorioBorradoresInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);
		$this->contenedor->registrar( RedactorMecanico::class, static fn (): RedactorMecanico => new RedactorMecanico() );
		$this->contenedor->registrar(
			RedactorInterface::class,
			fn ( Contenedor $c ): RedactorConFallbackMecanico => new RedactorConFallbackMecanico(
				$c->obtener( DecisionEditorial::class ),
				$c->obtener( RedactorSintetico::class ),
				$c->obtener( RedactorMecanico::class ),
				$c->obtener( RepositorioPiezasInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);

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

		$this->contenedor->registrar(
			ExportadorBancoPeriodistas::class,
			fn ( Contenedor $c ): ExportadorBancoPeriodistas => new ExportadorBancoPeriodistas(
				$c->obtener( RepositorioPeriodistasInterface::class ),
				$c->obtener( RepositorioMemoriaEditorialInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);
		$this->contenedor->registrar(
			ImportadorBancoPeriodistas::class,
			fn ( Contenedor $c ): ImportadorBancoPeriodistas => new ImportadorBancoPeriodistas(
				$c->obtener( RepositorioPeriodistasInterface::class ),
				$c->obtener( RepositorioMemoriaEditorialInterface::class ),
				$c->obtener( RelojInterface::class )
			)
		);
		$this->contenedor->registrar(
			RestBancoPeriodistas::class,
			fn ( Contenedor $c ): RestBancoPeriodistas => new RestBancoPeriodistas(
				$c->obtener( ExportadorBancoPeriodistas::class ),
				$c->obtener( ImportadorBancoPeriodistas::class )
			)
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
		$this->contenedor->obtener( RestBancoPeriodistas::class )->registrar();
	}
}
