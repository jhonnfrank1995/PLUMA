<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Investigacion\Expediente;

/**
 * Formatea un Expediente como texto plano para enviarlo como `material` de
 * una `PeticionLenguaje` (GOVERNANCE §2.4: el redactor solo conoce el
 * expediente — cada hecho viaja con su estado de verificación, para que el
 * modelo pueda distinguir verificado de atribuido de disputado).
 */
final class FormateadorExpediente {

	public static function comoTexto( Expediente $expediente ): string {
		$lineas   = array();
		$lineas[] = 'Tendencia de origen: ' . $expediente->tendenciaOrigen;
		$lineas[] = '';
		$lineas[] = 'Hechos del expediente (única fuente de verdad permitida):';

		foreach ( $expediente->hechos as $indice => $hecho ) {
			$lineas[] = sprintf(
				'[%d] (%s, %s) %s — fuente: %s',
				$indice + 1,
				$hecho->nivel->value,
				$hecho->fecha->format( 'Y-m-d' ),
				$hecho->extracto,
				$hecho->url
			);
		}

		return implode( "\n", $lineas );
	}
}
