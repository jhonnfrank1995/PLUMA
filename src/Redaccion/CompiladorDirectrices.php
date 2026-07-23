<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Traduce la Conducta de un periodista (diales, reglas, matriz de tonos) a
 * las `directrices` de estilo que viajan en la `PeticionLenguaje` (Libro
 * Cap. 5.6: "con la voz del periodista — sus diales, muletillas y
 * prohibiciones inyectados como directrices de estilo").
 *
 * La lógica editorial (`RedactorSintetico`) no conoce la forma interna de la
 * Conducta: solo consume el texto que este compilador produce.
 */
final class CompiladorDirectrices {

	/**
	 * @return array{etiqueta: string, bajo: string, alto: string}
	 */
	private function ancla( string $dial ): array {
		// Efectos "bajo"/"alto" documentados literalmente en el Libro Cap. 5.3.
		return match ( $dial ) {
			'agudezaCritica' => array(
				'etiqueta' => 'Agudeza crítica',
				'bajo'     => 'relata con neutralidad',
				'alto'     => 'interroga motivos, señala contradicciones, nombra ganadores y perdedores',
			),
			'humor'          => array(
				'etiqueta' => 'Humor',
				'bajo'     => 'tono sobrio',
				'alto'     => 'ironía recurrente, remates cómicos',
			),
			'formalidad'     => array(
				'etiqueta' => 'Formalidad',
				'bajo'     => 'cercano, coloquial',
				'alto'     => 'registro de columna dominical',
			),
			'vehemencia'     => array(
				'etiqueta' => 'Vehemencia',
				'bajo'     => 'matiza, concede',
				'alto'     => 'afirma, desafía al lector',
			),
			'empatia'        => array(
				'etiqueta' => 'Empatía',
				'bajo'     => 'distante',
				'alto'     => 'centra la pieza en el impacto humano',
			),
			'densidadDatos'  => array(
				'etiqueta' => 'Densidad de datos',
				'bajo'     => 'narrativo',
				'alto'     => 'cada afirmación lleva su número',
			),
			default          => array(
				'etiqueta' => $dial,
				'bajo'     => '',
				'alto'     => '',
			),
		};
	}

	private function lineaDial( string $dial, int $valor ): string {
		$ancla = $this->ancla( $dial );

		return sprintf( '%s: %d/100 (0 = %s; 100 = %s).', $ancla['etiqueta'], $valor, $ancla['bajo'], $ancla['alto'] );
	}

	public function compilar(
		Periodista $periodista,
		Tono $tonoDominante,
		Tono $tonoApoyo,
		NivelSatiraPermitida $nivelSatiraPermitida
	): string {
		$conducta = $periodista->conductaActual;
		$diales   = $conducta->diales;
		$reglas   = $conducta->reglas;

		$bloques   = array();
		$bloques[] = sprintf( 'Eres %s, %s de la redacción. %s', $periodista->nombre, $periodista->rol->value, $periodista->biografia );
		$bloques[] = 'Línea editorial (filtro de toda tesis que defiendas): ' . $reglas->lineaEditorial;

		$bloques[] = implode(
			"\n",
			array(
				'Diales de temperamento:',
				$this->lineaDial( 'agudezaCritica', $diales->agudezaCritica ),
				$this->lineaDial( 'humor', $diales->humor ),
				$this->lineaDial( 'formalidad', $diales->formalidad ),
				$this->lineaDial( 'vehemencia', $diales->vehemencia ),
				$this->lineaDial( 'empatia', $diales->empatia ),
				$this->lineaDial( 'densidadDatos', $diales->densidadDatos ),
			)
		);

		$bloques[] = $this->directrizSatira( $diales->satira, $nivelSatiraPermitida );
		$bloques[] = sprintf( 'Tono dominante de esta pieza: %s. Tono de apoyo: %s.', $tonoDominante->value, $tonoApoyo->value );
		$bloques[] = sprintf( 'Extensión objetivo: aproximadamente %d palabras.', $diales->longitudPalabrasObjetivo() );

		if ( array() !== $reglas->muletillas ) {
			$bloques[] = 'Rasgos de voz reconocibles (úsalos con moderación — como mucho uno por pieza, nunca todos juntos, jamás de forma paródica): '
				. implode( '; ', $reglas->muletillas );
		}

		if ( array() !== $reglas->lineasRojas ) {
			$bloques[] = 'Líneas rojas personales — jamás bromees ni las cruces: ' . implode( '; ', $reglas->lineasRojas );
		}

		$bloques[] = sprintf(
			'Te diriges al lector %s. Estilo de pregunta final: "%s".',
			TratamientoLector::Tu === $reglas->tratamientoLector ? 'de tú' : 'de usted',
			$reglas->estiloPreguntaFinal
		);

		$bloques[] = 'Vocabulario y frases PROHIBIDAS (nunca las uses, ni variaciones cercanas): '
			. implode( ', ', VocabularioProhibidoGlobal::combinarCon( $reglas->vocabularioProhibido ) );

		return implode( "\n\n", $bloques );
	}

	private function directrizSatira( int $dialSatira, NivelSatiraPermitida $nivelPermitido ): string {
		if ( NivelSatiraPermitida::Bloqueada === $nivelPermitido ) {
			// Regla de sistema inviolable (Libro Cap. 5.3): se antepone al dial del periodista sin excepción.
			return 'SÁTIRA BLOQUEADA POR SISTEMA para esta pieza: bajo ninguna circunstancia uses exageración satírica, ironía cruel o humor a costa de víctimas o afectados.';
		}

		$permiso = match ( $nivelPermitido ) {
			NivelSatiraPermitida::No           => 'no uses sátira en esta pieza',
			NivelSatiraPermitida::ConModeracion => 'puedes usar sátira con moderación, en pasajes puntuales',
			NivelSatiraPermitida::EnRemate      => 'puedes usar sátira solo en el remate final',
			NivelSatiraPermitida::PiezaCompleta => 'puedes construir la pieza entera con tono satírico',
		};

		return sprintf( 'Sátira (dial %d/100 de este periodista): para este tipo de noticia, %s.', $dialSatira, $permiso );
	}
}
