<?php

declare(strict_types=1);

namespace Pluma\Proveedores;

use Pluma\Kernel\RelojInterface;

/**
 * Presupuesto diario de gasto en el proveedor de lenguaje (Libro Cap. 9.4,
 * pl-proveedor-ia §3): se verifica ANTES de cada llamada, no después.
 * Al 80% se marca aviso; al 100% la generación se pausa (la publicación de
 * lo ya aprobado nunca se pausa por presupuesto).
 */
final class PresupuestoLenguaje {

	public const OPCION_LIMITE_DIARIO = 'pluma_presupuesto_diario_usd';
	public const OPCION_GASTO         = 'pluma_gasto_lenguaje';
	public const TRANSIENT_AVISO_80   = 'pluma_presupuesto_aviso_80';
	private const LIMITE_DEFECTO_USD  = 5.0;

	public function __construct( private readonly RelojInterface $reloj ) {
	}

	public function limiteDiarioUsd(): float {
		$limite = get_option( self::OPCION_LIMITE_DIARIO, self::LIMITE_DEFECTO_USD );

		return is_numeric( $limite ) ? max( 0.0, (float) $limite ) : self::LIMITE_DEFECTO_USD;
	}

	public function gastoHoyUsd(): float {
		$registro = $this->registroDeHoy();

		return $registro['gasto'];
	}

	/**
	 * ¿Hay presupuesto para una llamada más? Se invoca ANTES de cada petición.
	 */
	public function disponible(): bool {
		return $this->gastoHoyUsd() < $this->limiteDiarioUsd();
	}

	/**
	 * Acumula el coste real devuelto por el proveedor y gestiona el aviso
	 * del 80% (una sola vez por día).
	 */
	public function registrarGasto( float $costeUsd ): void {
		$registro           = $this->registroDeHoy();
		$registro['gasto'] += max( 0.0, $costeUsd );

		update_option( self::OPCION_GASTO, $registro, false );

		$limite = $this->limiteDiarioUsd();

		if ( $limite > 0 && $registro['gasto'] >= $limite * 0.8 && false === get_transient( self::TRANSIENT_AVISO_80 ) ) {
			set_transient( self::TRANSIENT_AVISO_80, 1, DAY_IN_SECONDS );
			do_action( 'pluma/presupuesto_al_80', $registro['gasto'], $limite );
		}
	}

	/**
	 * @return array{dia: string, gasto: float}
	 */
	private function registroDeHoy(): array {
		$hoy      = $this->reloj->ahora()->format( 'Y-m-d' );
		$registro = get_option( self::OPCION_GASTO, array() );

		if ( ! is_array( $registro ) || ( $registro['dia'] ?? '' ) !== $hoy ) {
			return array(
				'dia'   => $hoy,
				'gasto' => 0.0,
			);
		}

		return array(
			'dia'   => $hoy,
			'gasto' => is_numeric( $registro['gasto'] ?? null ) ? (float) $registro['gasto'] : 0.0,
		);
	}
}
