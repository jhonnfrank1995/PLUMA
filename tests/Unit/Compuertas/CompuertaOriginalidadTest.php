<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Compuertas;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use Pluma\Compuertas\CompuertaOriginalidad;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 8.3: "si la pieza no añade, no sale" — anti scaled-content-abuse.
 *
 * @covers \Pluma\Compuertas\CompuertaOriginalidad
 */
final class CompuertaOriginalidadTest extends CasoDePruebaUnitario {

	private function expediente( string $extracto ): Expediente {
		return new Expediente(
			'x',
			array( new HechoFuente( $extracto, 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado ) )
		);
	}

	public function test_aprueba_un_texto_original_sin_solapamiento(): void {
		Functions\when( 'get_option' )->justReturn( false );

		$expediente = $this->expediente( 'El banco central subió la tasa de interés al nueve por ciento este martes por la mañana' );
		$texto      = 'La decisión sorprendió a los mercados: nunca antes se había movido tanto en un solo anuncio, y el análisis histórico muestra un patrón preocupante.';

		$diagnostico = ( new CompuertaOriginalidad() )->evaluar( $expediente, $texto, array() );

		self::assertTrue( $diagnostico->aprobada() );
		self::assertFalse( $diagnostico->solapamientoConFuentes );
		self::assertFalse( $diagnostico->solapamientoConSitioPropio );
	}

	public function test_reprueba_por_solapamiento_textual_con_las_fuentes(): void {
		Functions\when( 'get_option' )->justReturn( false );

		$extracto   = 'El banco central subió la tasa de interés al nueve por ciento este martes por la mañana';
		$expediente = $this->expediente( $extracto );
		$texto      = 'Según fuentes oficiales, ' . $extracto . ', algo inédito en la historia reciente.';

		$diagnostico = ( new CompuertaOriginalidad() )->evaluar( $expediente, $texto, array() );

		self::assertFalse( $diagnostico->aprobada() );
		self::assertTrue( $diagnostico->solapamientoConFuentes );
	}

	public function test_reprueba_por_solapamiento_con_una_pieza_propia_ya_publicada(): void {
		Functions\when( 'get_option' )->justReturn( false );

		$piezaPropia = 'La decisión sorprendió a los mercados: nunca antes se había movido tanto en un solo anuncio del banco central.';
		$expediente  = $this->expediente( 'un hecho totalmente distinto que no se repite en ningún otro texto de esta prueba' );

		$diagnostico = ( new CompuertaOriginalidad() )->evaluar( $expediente, $piezaPropia, array( $piezaPropia ) );

		self::assertFalse( $diagnostico->aprobada() );
		self::assertTrue( $diagnostico->solapamientoConSitioPropio );
		self::assertFalse( $diagnostico->solapamientoConFuentes );
	}

	public function test_reprueba_por_ganancia_de_informacion_insuficiente_aunque_no_haya_solapamiento_largo(): void {
		Functions\when( 'get_option' )->justReturn( 0.9 );

		// Reescritura casi palabra por palabra pero sin ninguna secuencia de 8+
		// palabras idéntica (para no disparar el solapamiento por sí solo):
		// prácticamente todos los 4-gramas siguen siendo los mismos que la fuente.
		$extracto   = 'El gobierno anunció ayer una nueva medida económica que afectará a miles de familias en todo el país durante el próximo año fiscal';
		$expediente = $this->expediente( $extracto );
		$texto      = 'El gobierno anunció ayer una nueva medida económica. Afectará a miles de familias en todo el país. Será durante el próximo año fiscal.';

		$diagnostico = ( new CompuertaOriginalidad() )->evaluar( $expediente, $texto, array() );

		self::assertFalse( $diagnostico->aprobada() );
		self::assertLessThan( 0.9, $diagnostico->ratioGananciaInformacion );
	}

	public function test_el_umbral_de_ganancia_es_configurable(): void {
		Functions\when( 'get_option' )->justReturn( 0.0 );

		$expediente = $this->expediente( 'un hecho cualquiera que aparece en el expediente de esta prueba unitaria' );
		$texto      = 'un hecho cualquiera que aparece en el expediente de otra manera muy distinta y con contexto propio añadido';

		$diagnostico = ( new CompuertaOriginalidad() )->evaluar( $expediente, $texto, array() );

		self::assertSame( 0.0, $diagnostico->umbralGananciaMinima );
	}
}
