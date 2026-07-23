<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Datos\RepositorioVocabulario;
use Pluma\Kernel\Activador;
use Pluma\Kernel\RelojSistema;
use Pluma\Taxonomia\TipoVocabulario;
use WP_UnitTestCase;

/**
 * Repositorio `pluma_vocabulario` (Libro Cap. 7.1-7.2) contra tabla real
 * (pl-testing: "Integración (wp-env): repositorios pluma_*").
 *
 * `pluma_vocabulario` es la primera tabla GENUINAMENTE NUEVA desde que el
 * esquema original se instaló en este entorno de pruebas — a diferencia de
 * un `ALTER TABLE` sobre una tabla ya existente, un `CREATE TABLE` dentro de
 * un método de test normal cae bajo el filtro `_create_temporary_tables` de
 * `WP_UnitTestCase` (activado en `set_up()`), que la reescribe como tabla
 * TEMPORAL de MySQL — y las tablas temporales SÍ participan del `ROLLBACK`
 * transaccional entre tests, así que desaparecen antes del siguiente test.
 * Activar el esquema en `set_up_before_class()` (antes de que ese filtro
 * exista) evita el problema — mismo patrón que usa el propio test de
 * `dbDelta` del núcleo de WordPress (`tests/phpunit/tests/dbdelta.php`).
 *
 * @covers \Pluma\Datos\RepositorioVocabulario
 */
final class RepositorioVocabularioTest extends WP_UnitTestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		Activador::activar( new RelojSistema(), '0.8.0' );
	}

	public function test_crear_y_obtener_por_tipo_y_slug(): void {
		global $wpdb;
		$repo  = new RepositorioVocabulario( $wpdb );
		$reloj = new RelojSistema();

		$slug = 'inteligencia-artificial-' . uniqid();
		$id   = $repo->crear( TipoVocabulario::Etiqueta, 'Inteligencia Artificial', $slug, array( 'IA' ), true, $reloj->ahora() );

		self::assertGreaterThan( 0, $id );

		$entrada = $repo->obtenerPorTipoYSlug( TipoVocabulario::Etiqueta, $slug );

		self::assertNotNull( $entrada );
		self::assertSame( 'Inteligencia Artificial', $entrada->nombre );
		self::assertSame( array( 'IA' ), $entrada->alias );
		self::assertTrue( $entrada->enCuarentena );
		self::assertSame( 0, $entrada->vecesUsada );
	}

	public function test_devuelve_null_si_no_existe_el_slug(): void {
		global $wpdb;
		$repo = new RepositorioVocabulario( $wpdb );

		self::assertNull( $repo->obtenerPorTipoYSlug( TipoVocabulario::Etiqueta, 'slug-inexistente-' . uniqid() ) );
	}

	public function test_incrementar_uso_y_salir_de_cuarentena(): void {
		global $wpdb;
		$repo  = new RepositorioVocabulario( $wpdb );
		$reloj = new RelojSistema();

		$slug = 'reforma-pensional-' . uniqid();
		$id   = $repo->crear( TipoVocabulario::Etiqueta, 'Reforma Pensional', $slug, array(), true, $reloj->ahora() );

		self::assertTrue( $repo->incrementarUso( $id, $reloj->ahora() ) );
		self::assertTrue( $repo->incrementarUso( $id, $reloj->ahora() ) );
		self::assertTrue( $repo->incrementarUso( $id, $reloj->ahora() ) );

		$entrada = $repo->obtenerPorTipoYSlug( TipoVocabulario::Etiqueta, $slug );
		self::assertNotNull( $entrada );
		self::assertSame( 3, $entrada->vecesUsada );
		self::assertTrue( $entrada->enCuarentena );

		self::assertTrue( $repo->salirDeCuarentena( $id, $reloj->ahora() ) );

		$entradaActualizada = $repo->obtenerPorTipoYSlug( TipoVocabulario::Etiqueta, $slug );
		self::assertNotNull( $entradaActualizada );
		self::assertFalse( $entradaActualizada->enCuarentena );
	}

	public function test_obtener_por_tipo_no_mezcla_categorias_y_etiquetas(): void {
		global $wpdb;
		$repo  = new RepositorioVocabulario( $wpdb );
		$reloj = new RelojSistema();

		$sufijo = uniqid();
		$repo->crear( TipoVocabulario::Categoria, 'Economía ' . $sufijo, 'economia-' . $sufijo, array(), false, $reloj->ahora() );
		$repo->crear( TipoVocabulario::Etiqueta, 'Banco Central ' . $sufijo, 'banco-central-' . $sufijo, array(), true, $reloj->ahora() );

		$categorias = $repo->obtenerPorTipo( TipoVocabulario::Categoria );

		self::assertNotEmpty( $categorias );
		foreach ( $categorias as $entrada ) {
			self::assertSame( TipoVocabulario::Categoria, $entrada->tipo );
		}
	}
}
