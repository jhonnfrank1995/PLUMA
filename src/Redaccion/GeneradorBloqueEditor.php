<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PropositoLenguaje;

/**
 * Fase 7 del ciclo editorial (Libro Cap. 5.7): "el comentario... 2–4 líneas
 * en primera persona... con una postura más desnuda que el cuerpo" y "la
 * pregunta... nunca el genérico ¿qué opinas?... plantea el dilema concreto
 * de la noticia en segunda persona".
 */
final class GeneradorBloqueEditor {

	private const MAX_TOKENS_RESPUESTA = 350;

	public function __construct( private readonly LenguajeInterface $proveedor ) {
	}

	/**
	 * @throws DecisionEditorialException si la respuesta no trae comentario y pregunta, o llegó truncada.
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException
	 */
	public function generar( Periodista $periodista, ClasificacionNoticia $clasificacion, string $tesis ): BloqueEditor {
		$directrices = implode(
			"\n",
			array(
				"Eres {$periodista->nombre}, cerrando tu propia pieza con el Bloque del Editor.",
				'El comentario: 2-4 líneas en primera persona, con una postura MÁS desnuda y personal que el cuerpo del artículo (el cuerpo argumenta; el comentario confiesa).',
				'La pregunta: NUNCA el genérico "¿qué opinas?". Plantea el dilema concreto de la noticia en segunda persona, de forma dicotómica pero con matiz (ej. "¿Aceptarías X a cambio de Y?", "¿A quién le crees aquí, y por qué?").',
				'Responde ÚNICAMENTE con un objeto JSON de esta forma exacta: {"comentario": string, "pregunta": string}',
			)
		);

		$material = sprintf( "Tesis del artículo: %s\nPolaridad detectada (actores y disputa): %s", $tesis, $clasificacion->polaridad );

		$peticion  = new PeticionLenguaje( PropositoLenguaje::BloqueEditor, $directrices, $material, self::MAX_TOKENS_RESPUESTA );
		$respuesta = $this->proveedor->completar( $peticion );
		VerificadorTruncamiento::asegurar( $respuesta );
		$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );

		if ( ! isset( $datos['comentario'], $datos['pregunta'] ) || ! is_string( $datos['comentario'] ) || ! is_string( $datos['pregunta'] ) ) {
			throw new DecisionEditorialException( 'El Bloque del Editor no trae comentario y pregunta.' );
		}

		return new BloqueEditor( $datos['comentario'], $datos['pregunta'] );
	}
}
