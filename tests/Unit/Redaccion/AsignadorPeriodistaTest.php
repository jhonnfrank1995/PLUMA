<?php

declare(strict_types=1);

namespace Pluma\Tests\Unit\Redaccion;

use DateTimeImmutable;
use Pluma\Redaccion\AsignadorPeriodista;
use Pluma\Redaccion\ClasificacionNoticia;
use Pluma\Redaccion\ConductaVersion;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\Especialidad;
use Pluma\Redaccion\EstadoPeriodista;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\NovedadNoticia;
use Pluma\Redaccion\DecisionEditorialException;
use Pluma\Redaccion\Periodista;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Redaccion\RolPeriodista;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Redaccion\TratamientoLector;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * Paso 2 del Algoritmo de Decisión Editorial (Libro Cap. 5.5).
 *
 * @covers \Pluma\Redaccion\AsignadorPeriodista
 */
final class AsignadorPeriodistaTest extends CasoDePruebaUnitario {

	private function periodista( int $id, string $vertical, int $dominio, string $lineaEditorial ): Periodista {
		$diales   = new Diales( 50, 50, 50, 50, 50, 50, 50, 50 );
		$reglas   = new ReglasConducta( $lineaEditorial, array(), array(), array(), TratamientoLector::Tu, '¿Y tú qué opinas?' );
		$matriz   = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::DatoEconomico, Tono::Analitico, Tono::Persuasivo, NivelSatiraPermitida::No ) )
		);
		$conducta = new ConductaVersion( $id, $id, $diales, $reglas, $matriz, new DateTimeImmutable( '2026-07-22T12:00:00+00:00' ) );

		return new Periodista(
			$id,
			"Periodista {$id}",
			null,
			'Bio.',
			RolPeriodista::Columnista,
			array( new Especialidad( $vertical, $dominio ) ),
			EstadoPeriodista::Activo,
			$conducta,
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' ),
			new DateTimeImmutable( '2026-01-01T00:00:00+00:00' )
		);
	}

	private function clasificacion( string $tema, string $polaridad = '' ): ClasificacionNoticia {
		return new ClasificacionNoticia( $tema, 30, $polaridad, NovedadNoticia::Primicia, 50, TipoNoticia::DatoEconomico );
	}

	public function test_lanza_excepcion_si_no_hay_candidatos(): void {
		$this->expectException( DecisionEditorialException::class );

		( new AsignadorPeriodista() )->asignar( array(), $this->clasificacion( 'economia' ), array(), array() );
	}

	public function test_elige_al_periodista_con_mayor_dominio_del_vertical(): void {
		$experto = $this->periodista( 1, 'economia', 5, 'neutral' );
		$novato  = $this->periodista( 2, 'economia', 1, 'neutral' );

		$elegido = ( new AsignadorPeriodista() )->asignar( array( $novato, $experto ), $this->clasificacion( 'economia' ), array(), array() );

		self::assertSame( $experto->id, $elegido->id );
	}

	public function test_un_periodista_sin_la_especialidad_no_gana_frente_a_uno_con_dominio(): void {
		$sinEspecialidad = $this->periodista( 1, 'cultura', 5, 'neutral' );
		$conEspecialidad = $this->periodista( 2, 'economia', 3, 'neutral' );

		$elegido = ( new AsignadorPeriodista() )->asignar(
			array( $sinEspecialidad, $conEspecialidad ),
			$this->clasificacion( 'economia' ),
			array(),
			array()
		);

		self::assertSame( $conEspecialidad->id, $elegido->id );
	}

	public function test_el_balance_de_carga_penaliza_a_quien_ya_tiene_piezas_asignadas_hoy(): void {
		$sobrecargado = $this->periodista( 1, 'economia', 3, 'neutral' );
		$disponible   = $this->periodista( 2, 'economia', 3, 'neutral' );

		$elegido = ( new AsignadorPeriodista() )->asignar(
			array( $sobrecargado, $disponible ),
			$this->clasificacion( 'economia' ),
			array(
				$sobrecargado->id => 5,
				$disponible->id   => 0,
			),
			array()
		);

		self::assertSame( $disponible->id, $elegido->id );
	}

	public function test_el_historial_de_cobertura_favorece_a_quien_ya_siguio_el_tema(): void {
		$conHistorial = $this->periodista( 1, 'economia', 3, 'neutral' );
		$sinHistorial = $this->periodista( 2, 'economia', 3, 'neutral' );

		$elegido = ( new AsignadorPeriodista() )->asignar(
			array( $conHistorial, $sinHistorial ),
			$this->clasificacion( 'economia' ),
			array(),
			array(
				$conHistorial->id => true,
				$sinHistorial->id => false,
			)
		);

		self::assertSame( $conHistorial->id, $elegido->id );
	}

	public function test_la_afinidad_de_linea_editorial_favorece_al_periodista_mas_alineado(): void {
		$alineado    = $this->periodista( 1, 'economia', 3, 'Escéptica del poder corporativo y la inflacion' );
		$desalineado = $this->periodista( 2, 'economia', 3, 'Optimista de la cultura pop y los videojuegos' );

		$elegido = ( new AsignadorPeriodista() )->asignar(
			array( $desalineado, $alineado ),
			$this->clasificacion( 'economia', 'gobierno vs. corporaciones por la inflacion' ),
			array(),
			array()
		);

		self::assertSame( $alineado->id, $elegido->id );
	}
}
