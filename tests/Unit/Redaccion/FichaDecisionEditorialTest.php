<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Redaccion\CandidatoTesis;
use Pluma\Redaccion\ClasificacionNoticia;
use Pluma\Redaccion\EsqueletoPieza;
use Pluma\Redaccion\FichaDecisionEditorial;
use Pluma\Redaccion\NovedadNoticia;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Tests\Unit\CasoDePruebaUnitario;
use RuntimeException;

/**
 * pl-periodistas §Contratos innegociables 7: la Ficha de Decisión Editorial
 * es trazabilidad pura — la tesis elegida debe ser recuperable sin ambigüedad.
 *
 * @covers \Pluma\Redaccion\FichaDecisionEditorial
 */
final class FichaDecisionEditorialTest extends CasoDePruebaUnitario {

	private function candidato( string $tesis ): CandidatoTesis {
		return new CandidatoTesis( $tesis, 70.0, 70.0, 70.0, 70.0 );
	}

	private function ficha( int $indiceElegido, int $totalCandidatos ): FichaDecisionEditorial {
		$candidatos = array();

		for ( $i = 0; $i < $totalCandidatos; $i++ ) {
			$candidatos[] = $this->candidato( "tesis {$i}" );
		}

		return new FichaDecisionEditorial(
			1,
			1,
			new ClasificacionNoticia( 'economia', 30, 'polaridad', NovedadNoticia::Primicia, 50, TipoNoticia::DatoEconomico ),
			$candidatos,
			$indiceElegido,
			Tono::Analitico,
			Tono::Persuasivo,
			new EsqueletoPieza( 'gancho', 'hechos', array( 'm1', 'm2' ), 'contra', 'remate' ),
			new DateTimeImmutable( '2026-07-22T12:00:00+00:00' )
		);
	}

	public function test_tesis_elegida_devuelve_el_candidato_en_el_indice_elegido(): void {
		self::assertSame( 'tesis 1', $this->ficha( 1, 3 )->tesisElegida()->tesis );
	}

	public function test_tesis_elegida_lanza_excepcion_si_el_indice_esta_fuera_de_rango(): void {
		$this->expectException( RuntimeException::class );

		$this->ficha( 5, 3 )->tesisElegida();
	}

	public function test_ida_y_vuelta_por_array_preserva_la_tesis_elegida(): void {
		$original     = $this->ficha( 2, 3 );
		$reconstruida = FichaDecisionEditorial::desdeArray( $original->aArray() );

		self::assertSame( $original->tesisElegida()->tesis, $reconstruida->tesisElegida()->tesis );
		self::assertSame( $original->tonoDominante, $reconstruida->tonoDominante );
		self::assertSame( $original->clasificacion->tipoNoticia, $reconstruida->clasificacion->tipoNoticia );
	}
}
