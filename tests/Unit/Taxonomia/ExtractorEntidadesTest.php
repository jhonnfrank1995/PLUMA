<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Taxonomia;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;
use Pluma\Investigacion\NivelVerificacion;
use Pluma\Taxonomia\ExtractorEntidades;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Libro Cap. 7.2, punto 1 — extracción de entidades del expediente
 * (heurística de secuencias capitalizadas, no NER real, ver docblock de la clase).
 *
 * @covers \Pluma\Taxonomia\ExtractorEntidades
 */
final class ExtractorEntidadesTest extends CasoDePruebaUnitario {

	private function hecho( string $extracto ): HechoFuente {
		return new HechoFuente( $extracto, 'https://example.com', new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ), NivelVerificacion::Verificado );
	}

	public function test_extrae_una_entidad_multi_palabra_con_conector(): void {
		$expediente = new Expediente(
			'x',
			array(
				$this->hecho( 'El Banco de la Republica anuncio cambios en su politica monetaria.' ),
				$this->hecho( 'Banco de la Republica confirmo que el movimiento fue distinto al esperado.' ),
			)
		);

		$entidades = ( new ExtractorEntidades() )->extraer( $expediente, '' );

		self::assertContains( 'Banco de la Republica', $entidades );
	}

	public function test_filtra_palabras_funcionales_capitalizadas_por_inicio_de_frase(): void {
		$expediente = new Expediente( 'x', array( $this->hecho( 'El presidente habló ayer. La reunión terminó tarde.' ) ) );

		$entidades = ( new ExtractorEntidades() )->extraer( $expediente, '' );

		self::assertNotContains( 'El', $entidades );
		self::assertNotContains( 'La', $entidades );
	}

	public function test_una_mencion_unica_no_es_central_sin_aparecer_en_la_tesis(): void {
		$expediente = new Expediente( 'x', array( $this->hecho( 'Colombia firmó un acuerdo comercial ayer.' ) ) );

		$entidades = ( new ExtractorEntidades() )->extraer( $expediente, 'El acuerdo cambia las reglas del comercio regional.' );

		self::assertNotContains( 'Colombia', $entidades );
	}

	public function test_una_mencion_unica_es_central_si_aparece_en_la_tesis(): void {
		$expediente = new Expediente( 'x', array( $this->hecho( 'Colombia firmó un acuerdo comercial ayer.' ) ) );

		$entidades = ( new ExtractorEntidades() )->extraer( $expediente, 'Colombia gana con este acuerdo comercial.' );

		self::assertContains( 'Colombia', $entidades );
	}

	public function test_dos_menciones_son_centrales_sin_necesidad_de_la_tesis(): void {
		$expediente = new Expediente(
			'x',
			array(
				$this->hecho( 'Microsoft lanzó un nuevo producto.' ),
				$this->hecho( 'Microsoft confirmó que el lanzamiento fue exitoso.' ),
			)
		);

		$entidades = ( new ExtractorEntidades() )->extraer( $expediente, '' );

		self::assertContains( 'Microsoft', $entidades );
	}
}
