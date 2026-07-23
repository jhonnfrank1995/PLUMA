<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

/**
 * Lee la configuración de cadencia desde `wp_options` (Libro Cap. 9.2),
 * editable por el editor en el panel (Etapa 4) — nunca hardcodeada en la
 * lógica del Orquestador. Pisos de fábrica razonables si el editor no ha
 * configurado nada todavía.
 */
final class LectorConfiguracionCadencia {

	public const OPCION_CUOTA_OBJETIVO        = 'pluma_cuota_objetivo';
	public const OPCION_CUOTA_MINIMA          = 'pluma_cuota_minima';
	public const OPCION_CUOTA_MAXIMA          = 'pluma_cuota_maxima';
	public const OPCION_VENTANAS              = 'pluma_ventanas_publicacion';
	public const OPCION_SEPARACION_MINIMA_MIN = 'pluma_separacion_minima_minutos';
	public const OPCION_JITTER_MAXIMO_MIN     = 'pluma_jitter_maximo_minutos';
	public const OPCION_TOPES_POR_VERTICAL    = 'pluma_topes_por_vertical';
	public const OPCION_TOPES_POR_PERIODISTA  = 'pluma_topes_por_periodista';

	private const CUOTA_OBJETIVO_DEFECTO    = 6;
	private const CUOTA_MINIMA_DEFECTO      = 3;
	private const CUOTA_MAXIMA_DEFECTO      = 8;
	private const SEPARACION_MINIMA_DEFECTO = 45;
	private const JITTER_MAXIMO_DEFECTO     = 15;

	/**
	 * @return list<array{horaInicio: int, horaFin: int, peso: int}>
	 */
	private const VENTANAS_DEFECTO = array(
		array(
			'horaInicio' => 7,
			'horaFin'    => 9,
			'peso'       => 3,
		),
		array(
			'horaInicio' => 12,
			'horaFin'    => 14,
			'peso'       => 2,
		),
		array(
			'horaInicio' => 19,
			'horaFin'    => 21,
			'peso'       => 3,
		),
	);

	public function leer(): ConfiguracionCadencia {
		return new ConfiguracionCadencia(
			$this->entero( self::OPCION_CUOTA_OBJETIVO, self::CUOTA_OBJETIVO_DEFECTO ),
			$this->entero( self::OPCION_CUOTA_MINIMA, self::CUOTA_MINIMA_DEFECTO ),
			$this->entero( self::OPCION_CUOTA_MAXIMA, self::CUOTA_MAXIMA_DEFECTO ),
			$this->ventanas(),
			$this->entero( self::OPCION_SEPARACION_MINIMA_MIN, self::SEPARACION_MINIMA_DEFECTO ),
			$this->entero( self::OPCION_JITTER_MAXIMO_MIN, self::JITTER_MAXIMO_DEFECTO ),
			$this->topes( self::OPCION_TOPES_POR_VERTICAL ),
			$this->topesPorPeriodista()
		);
	}

	private function entero( string $opcion, int $defecto ): int {
		$valor = get_option( $opcion, $defecto );

		return is_numeric( $valor ) ? (int) $valor : $defecto;
	}

	/**
	 * @return list<VentanaPublicacion>
	 */
	private function ventanas(): array {
		$crudo = get_option( self::OPCION_VENTANAS, self::VENTANAS_DEFECTO );

		if ( ! is_array( $crudo ) || array() === $crudo ) {
			$crudo = self::VENTANAS_DEFECTO;
		}

		/** @var list<array{horaInicio: int, horaFin: int, peso: int}> $crudo */
		return array_map( static fn ( array $v ): VentanaPublicacion => VentanaPublicacion::desdeArray( $v ), $crudo );
	}

	/**
	 * @return array<string, int>
	 */
	private function topes( string $opcion ): array {
		$crudo = get_option( $opcion, array() );

		return is_array( $crudo ) ? array_map( 'intval', $crudo ) : array();
	}

	/**
	 * @return array<int, int>
	 */
	private function topesPorPeriodista(): array {
		$topes         = array();
		$periodistaIds = get_option( self::OPCION_TOPES_POR_PERIODISTA, array() );

		if ( ! is_array( $periodistaIds ) ) {
			return array();
		}

		foreach ( $periodistaIds as $periodistaId => $limite ) {
			$topes[ (int) $periodistaId ] = (int) $limite;
		}

		return $topes;
	}
}
