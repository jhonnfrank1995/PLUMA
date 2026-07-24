<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PropositoLenguaje;

/**
 * Respuestas asistidas a comentarios (Libro Cap. 5.7, "el compromiso de
 * respuesta"): borrador de respuesta del periodista a un comentario real y
 * sustantivo, en su propia voz — el editor humano lo aprueba con un clic
 * antes de publicarse (nunca automático).
 */
final class GeneradorRespuestaComentario {

	private const MAX_TOKENS_RESPUESTA = 300;

	public function __construct( private readonly LenguajeInterface $proveedor ) {
	}

	/**
	 * @throws DecisionEditorialException si la respuesta no trae texto, o llegó truncada.
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException
	 */
	public function generar( Periodista $periodista, string $temaArticulo, string $comentarioTexto ): string {
		$reglas = $periodista->conductaActual->reglas;

		$directrices = implode(
			"\n",
			array(
				"Eres {$periodista->nombre}, respondiendo en persona a un comentario real de un lector bajo tu propio artículo.",
				"Tu línea editorial: {$reglas->lineaEditorial}. Trato al lector: {$reglas->tratamientoLector->value}.",
				'La respuesta: 1-3 líneas, cercana y específica al comentario (nunca un agradecimiento genérico), manteniendo tu voz y postura del artículo.',
				'Nunca cruces tus líneas rojas: ' . implode( ', ', $reglas->lineasRojas ) . '.',
				'Responde ÚNICAMENTE con un objeto JSON de esta forma exacta: {"respuesta": string}',
			)
		);

		$material = sprintf( "Tema del artículo: %s\nComentario del lector: %s", $temaArticulo, $comentarioTexto );

		$peticion  = new PeticionLenguaje( PropositoLenguaje::RespuestaComentario, $directrices, $material, self::MAX_TOKENS_RESPUESTA );
		$respuesta = $this->proveedor->completar( $peticion );
		VerificadorTruncamiento::asegurar( $respuesta );
		$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );

		if ( ! isset( $datos['respuesta'] ) || ! is_string( $datos['respuesta'] ) || '' === trim( $datos['respuesta'] ) ) {
			throw new DecisionEditorialException( 'El borrador de respuesta al comentario no trae texto.' );
		}

		return $datos['respuesta'];
	}
}
