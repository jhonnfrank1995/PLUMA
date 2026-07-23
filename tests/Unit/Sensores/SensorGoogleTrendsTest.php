<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Sensores;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Proveedores\ProveedorTendenciasInterface;
use Pluma\Proveedores\TendenciaCruda;
use Pluma\Sensores\SensorGoogleTrends;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * @covers \Pluma\Sensores\SensorGoogleTrends
 */
final class SensorGoogleTrendsTest extends CasoDePruebaUnitario {

	public function test_nombre_identifica_la_fuente_de_senal(): void {
		$proveedor = new class() implements ProveedorTendenciasInterface {
			public function obtenerTendenciasCrudas(): array {
				return array();
			}
		};

		self::assertSame( 'google_trends', ( new SensorGoogleTrends( $proveedor ) )->nombre() );
	}

	public function test_detectar_devuelve_vacio_si_el_proveedor_no_trae_nada(): void {
		$proveedor = new class() implements ProveedorTendenciasInterface {
			public function obtenerTendenciasCrudas(): array {
				return array();
			}
		};

		self::assertSame( array(), ( new SensorGoogleTrends( $proveedor ) )->detectar() );
	}

	public function test_la_tendencia_de_mayor_trafico_del_lote_saca_velocidad_100(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$proveedor = new class() implements ProveedorTendenciasInterface {
			public function obtenerTendenciasCrudas(): array {
				return array(
					new TendenciaCruda( 'alto trafico', '10000+', new DateTimeImmutable(), array() ),
					new TendenciaCruda( 'bajo trafico', '1000+', new DateTimeImmutable(), array() ),
				);
			}
		};

		$detectadas = ( new SensorGoogleTrends( $proveedor ) )->detectar();

		self::assertSame( 100.0, $detectadas[0]->puntuacion->velocidad );
		self::assertSame( 10.0, $detectadas[1]->puntuacion->velocidad );
	}

	public function test_sin_verticales_configurados_la_afinidad_es_maxima(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$proveedor = new class() implements ProveedorTendenciasInterface {
			public function obtenerTendenciasCrudas(): array {
				return array( new TendenciaCruda( 'cualquier tema', '100+', new DateTimeImmutable(), array() ) );
			}
		};

		$detectadas = ( new SensorGoogleTrends( $proveedor ) )->detectar();

		self::assertSame( 100.0, $detectadas[0]->puntuacion->afinidad );
	}

	public function test_con_verticales_configurados_solo_coincide_por_subcadena(): void {
		Functions\when( 'get_option' )->justReturn( array( 'tecnologia', 'economia' ) );

		$proveedor = new class() implements ProveedorTendenciasInterface {
			public function obtenerTendenciasCrudas(): array {
				return array(
					new TendenciaCruda( 'nueva app de tecnologia', '100+', new DateTimeImmutable(), array() ),
					new TendenciaCruda( 'resultado de futbol', '100+', new DateTimeImmutable(), array() ),
				);
			}
		};

		$detectadas = ( new SensorGoogleTrends( $proveedor ) )->detectar();

		self::assertSame( 100.0, $detectadas[0]->puntuacion->afinidad );
		self::assertSame( 30.0, $detectadas[1]->puntuacion->afinidad );
	}

	public function test_conserva_termino_fecha_y_articulos_relacionados(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		$fecha     = new DateTimeImmutable( '2026-07-22T12:00:00+00:00' );
		$articulos = array(
			array(
				'titulo' => 'Un artículo',
				'url'    => 'https://example.com',
				'fuente' => 'Example',
			),
		);

		$proveedor = new class($fecha, $articulos) implements ProveedorTendenciasInterface {
			/**
			 * @param list<array{titulo: string, url: string, fuente: string}> $articulos
			 */
			public function __construct( private DateTimeImmutable $fecha, private array $articulos ) {
			}

			public function obtenerTendenciasCrudas(): array {
				return array( new TendenciaCruda( 'tendencia', '100+', $this->fecha, $this->articulos ) );
			}
		};

		$detectada = ( new SensorGoogleTrends( $proveedor ) )->detectar()[0];

		self::assertSame( $fecha, $detectada->detectadaEn );
		self::assertSame( $articulos, $detectada->articulosRelacionados );
	}
}
