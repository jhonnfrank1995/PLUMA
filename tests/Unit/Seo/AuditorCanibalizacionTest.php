<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Seo;

use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Seo\AuditorCanibalizacion;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 6.3 — auditoría de canibalización: "el motor verifica si el
 * sitio ya tiene una pieza posicionando por la misma keyword".
 *
 * @covers \Pluma\Seo\AuditorCanibalizacion
 */
final class AuditorCanibalizacionTest extends CasoDePruebaUnitario {

	public function test_reporta_canibalizacion_si_el_repositorio_encuentra_una_pieza_publicada(): void {
		$repo = $this->createMock( RepositorioPiezasInterface::class );
		$repo->method( 'existePiezaPublicadaConKeyword' )->with( 'reforma pensional', 10 )->willReturn( true );

		self::assertTrue( ( new AuditorCanibalizacion( $repo ) )->hayCanibalizacion( 'reforma pensional', 10 ) );
	}

	public function test_no_reporta_canibalizacion_si_no_hay_coincidencia(): void {
		$repo = $this->createMock( RepositorioPiezasInterface::class );
		$repo->method( 'existePiezaPublicadaConKeyword' )->willReturn( false );

		self::assertFalse( ( new AuditorCanibalizacion( $repo ) )->hayCanibalizacion( 'tema nuevo', 10 ) );
	}
}
