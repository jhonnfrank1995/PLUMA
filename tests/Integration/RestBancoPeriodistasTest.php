<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Admin\RestBancoPeriodistas;
use Pluma\Datos\RepositorioPeriodistas;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Nucleo;
use Pluma\Kernel\RelojSistema;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Export/import del banco de periodistas contra WordPress real (wp-env):
 * capacidad propia `pluma_gestionar_periodistas`, jamás `manage_options`
 * (pl-periodistas §8, CLAUDE.md § Estándares WordPress).
 *
 * @covers \Pluma\Admin\RestBancoPeriodistas
 * @covers \Pluma\Redaccion\ExportadorBancoPeriodistas
 * @covers \Pluma\Redaccion\ImportadorBancoPeriodistas
 */
final class RestBancoPeriodistasTest extends WP_UnitTestCase {

	private function registrarRutas(): void {
		$nucleo = new Nucleo();
		$nucleo->contenedor()->obtener( RestBancoPeriodistas::class )->registrar();
		do_action( 'rest_api_init' );
	}

	public function test_exportar_rechaza_a_quien_no_tiene_la_capacidad(): void {
		$this->registrarRutas();

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/periodistas/exportar' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertContains( $respuesta->get_status(), array( 401, 403 ) );
	}

	public function test_administrador_puede_exportar_un_banco_vacio(): void {
		Activador::activar( new RelojSistema(), '0.8.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$peticion  = new WP_REST_Request( 'GET', '/pluma/v1/periodistas/exportar' );
		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 200, $respuesta->get_status() );
		$datos = $respuesta->get_data();
		self::assertSame( '1.0', $datos['version'] );
		self::assertSame( array(), $datos['periodistas'] );
	}

	public function test_importar_crea_el_periodista_y_se_refleja_en_una_exportacion_posterior(): void {
		global $wpdb;

		Activador::activar( new RelojSistema(), '0.8.0' );
		$adminId = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $adminId );

		$this->registrarRutas();

		$diales = new Diales( 80, 55, 40, 55, 75, 60, 60, 65 );
		$reglas = new ReglasConducta( 'linea editorial', array(), array(), array(), TratamientoLector::Tu, '¿Y tú?' );
		$matriz = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
		);

		$exportacion = array(
			'version'     => '1.0',
			'exportadoEn' => ( new RelojSistema() )->ahora()->format( DATE_ATOM ),
			'periodistas' => array(
				array(
					'nombre'            => 'Periodista Importado',
					'avatarUrl'         => null,
					'biografia'         => 'Bio.',
					'rol'               => RolPeriodista::Columnista->value,
					'especialidades'    => array(),
					'estado'            => EstadoPeriodista::Activo->value,
					'versionesConducta' => array(
						array(
							'diales'         => $diales->aArray(),
							'reglasConducta' => $reglas->aArray(),
							'matrizTonos'    => $matriz->aArray(),
						),
					),
					'memoria'           => array(),
				),
			),
		);

		$peticion = new WP_REST_Request( 'POST', '/pluma/v1/periodistas/importar' );
		$peticion->set_header( 'Content-Type', 'application/json' );
		$peticion->set_body( (string) wp_json_encode( $exportacion ) );

		$respuesta = rest_get_server()->dispatch( $peticion );

		self::assertSame( 201, $respuesta->get_status() );
		self::assertSame( 1, $respuesta->get_data()['importados'] );

		$repo       = new RepositorioPeriodistas( $wpdb );
		$importados = $repo->obtenerTodos();
		self::assertCount( 1, $importados );
		self::assertSame( 'Periodista Importado', $importados[0]->nombre );

		$peticionExportar  = new WP_REST_Request( 'GET', '/pluma/v1/periodistas/exportar' );
		$respuestaExportar = rest_get_server()->dispatch( $peticionExportar );
		self::assertCount( 1, $respuestaExportar->get_data()['periodistas'] );
	}
}
