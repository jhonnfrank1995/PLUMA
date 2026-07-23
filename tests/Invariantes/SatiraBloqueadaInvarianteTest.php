<?php

declare(strict_types=1);

namespace Pluma\Tests\Invariantes;

use Pluma\Redaccion\EntradaMatrizTono;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\NivelSatiraPermitida;
use Pluma\Redaccion\TipoNoticia;
use Pluma\Redaccion\Tono;
use Pluma\Tests\Unit\CasoDePruebaUnitario;

/**
 * GOVERNANCE §2.2 (primera capa) — "La degradación por sensibilidad
 * (tragedia/menores/salud → nunca Autónomo, sátira bloqueada)... y NINGUNA
 * opción de usuario la anula." Libro Cap. 5.3: el bloqueo de sátira en
 * Tragedia "no es un valor de la matriz: es una regla de sistema".
 *
 * La segunda capa de esta misma regla vive en `Pluma\Compuertas` (Etapa 3,
 * defensa en profundidad — dos capas independientes). Esta suite cubre la
 * primera: ningún periodista, por configuración que sea, puede terminar con
 * sátira permitida en una pieza clasificada como Tragedia.
 *
 * Si este test se pone en rojo, un periodista con el dial de sátira al
 * máximo podría bromear sobre una tragedia real — el escenario que la regla
 * existe para impedir.
 */
final class SatiraBloqueadaInvarianteTest extends CasoDePruebaUnitario {

	public function test_una_matriz_sin_fila_de_tragedia_configurada_bloquea_satira_igual(): void {
		$matriz = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::CulturaViral, Tono::Humoristico, Tono::Opinion, NivelSatiraPermitida::PiezaCompleta ) )
		);

		self::assertSame( NivelSatiraPermitida::Bloqueada, $matriz->paraTipo( TipoNoticia::Tragedia )->nivelSatira );
	}

	/**
	 * El caso adversarial explícito: alguien (un bug, un import corrupto, un
	 * endpoint futuro sin blindar) construye una matriz que INTENTA declarar
	 * sátira "pieza_completa" para Tragedia. La regla de sistema debe
	 * ignorar ese intento sin excepción.
	 */
	public function test_un_intento_explicito_de_permitir_satira_en_tragedia_es_ignorado(): void {
		$matrizMaliciosa = MatrizTonos::desdeFilas(
			array( new EntradaMatrizTono( TipoNoticia::Tragedia, Tono::Humoristico, Tono::Opinion, NivelSatiraPermitida::PiezaCompleta ) )
		);

		self::assertSame(
			NivelSatiraPermitida::Bloqueada,
			$matrizMaliciosa->paraTipo( TipoNoticia::Tragedia )->nivelSatira,
			'Ninguna configuración, ni siquiera una explícita, puede desbloquear sátira en Tragedia.'
		);
		self::assertSame( Tono::InformativoEmpatico, $matrizMaliciosa->paraTipo( TipoNoticia::Tragedia )->tonoDominante );
	}

	/**
	 * Un archivo de importación del banco de periodistas (pl-periodistas §8)
	 * manipulado a mano para declarar sátira "pieza_completa" en Tragedia
	 * — el escenario real de un JSON editado fuera del producto — no puede
	 * colar la regla al pasar por `desdeArray()`.
	 */
	public function test_un_array_manipulado_a_mano_no_cuela_satira_en_tragedia(): void {
		$datosManipulados = array(
			'tragedia' => array(
				'tipoNoticia'   => 'tragedia',
				'tonoDominante' => 'humoristico',
				'tonoApoyo'     => 'opinion',
				'nivelSatira'   => 'pieza_completa',
			),
		);

		$reconstruida = MatrizTonos::desdeArray( $datosManipulados );

		self::assertSame( NivelSatiraPermitida::Bloqueada, $reconstruida->paraTipo( TipoNoticia::Tragedia )->nivelSatira );
	}
}
