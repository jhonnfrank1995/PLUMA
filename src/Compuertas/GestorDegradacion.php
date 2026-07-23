<?php

declare(strict_types=1);

namespace Pluma\Compuertas;

/**
 * Degradación automática de modo por sensibilidad (Libro Cap. 2.4/8.2): "no
 * es opcional en el diseño: es el seguro de vida legal y reputacional del
 * producto." Ninguna opción de usuario la anula.
 *
 * Menores es el caso más severo (protección infantil): fuerza Piloto
 * completo, sin importar el modo global. El resto de señales de
 * sensibilidad (tragedia, salud, violencia) degradan Autónomo → Copiloto —
 * Copiloto y Piloto ya tienen supervisión humana incorporada, así que no
 * degradan más.
 */
final class GestorDegradacion {

	public function modoEfectivo( ModoOperacion $modoGlobal, DiagnosticoRiesgo $riesgo ): ModoOperacion {
		if ( $riesgo->implicaMenores ) {
			return ModoOperacion::Piloto;
		}

		if ( $riesgo->requiereDegradacionPorSensibilidad() && ModoOperacion::Autonomo === $modoGlobal ) {
			return ModoOperacion::Copiloto;
		}

		return $modoGlobal;
	}
}
