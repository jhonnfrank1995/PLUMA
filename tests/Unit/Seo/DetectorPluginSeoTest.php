<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Pluma\Seo\DetectorPluginSeo;
use Pluma\Seo\TipoPluginSeo;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Detección verificada contra el código fuente oficial de Yoast SEO
 * (`WPSEO_VERSION`) y Rank Math (`RANK_MATH_VERSION`) — ver docblock de
 * `DetectorPluginSeo`. Cada test que define una constante corre en un
 * proceso PHP separado: las constantes de PHP no se pueden "des-definir",
 * así que sin aislamiento un test contaminaría a los demás.
 *
 * @covers \Pluma\Seo\DetectorPluginSeo
 */
final class DetectorPluginSeoTest extends CasoDePruebaUnitario {

	public function test_ninguno_si_no_hay_constante_de_ningun_plugin_definida(): void {
		self::assertSame( TipoPluginSeo::Ninguno, ( new DetectorPluginSeo() )->detectar() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_detecta_yoast_por_su_constante_de_version(): void {
		define( 'WPSEO_VERSION', '28.2' );

		self::assertSame( TipoPluginSeo::Yoast, ( new DetectorPluginSeo() )->detectar() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_detecta_rank_math_por_su_constante_de_version(): void {
		define( 'RANK_MATH_VERSION', '1.0.274' );

		self::assertSame( TipoPluginSeo::RankMath, ( new DetectorPluginSeo() )->detectar() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_yoast_tiene_prioridad_si_ambos_estan_activos(): void {
		define( 'WPSEO_VERSION', '28.2' );
		define( 'RANK_MATH_VERSION', '1.0.274' );

		self::assertSame( TipoPluginSeo::Yoast, ( new DetectorPluginSeo() )->detectar() );
	}
}
