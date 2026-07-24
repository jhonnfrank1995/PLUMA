<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PresupuestoLenguaje;
use Pluma\Proveedores\ProveedorLenguajeException;
use Pluma\Proveedores\PropositoLenguaje;

/**
 * Memoria de audiencia (Libro Cap. 5.7, Etapa 5): extrae un aprendizaje
 * breve de un comentario sustantivo real, para poblar
 * {@see TipoMemoria::Audiencia}. Modelo económico y determinista
 * (`PropositoLenguaje::AnalizarAudiencia`), mismo patrón que
 * `Pluma\Sensores\ComparadorHistorias`.
 *
 * **Fail-safe obligatorio**: sin presupuesto, con el proveedor caído, o con
 * una respuesta no interpretable, `analizar()` devuelve `null` en vez de
 * lanzar — un fallo aquí jamás debe bloquear el tick del Orquestador.
 */
final class AnalizadorAudiencia {

	private const MAX_TOKENS_RESPUESTA = 200;

	public function __construct(
		private readonly LenguajeInterface $proveedor,
		private readonly PresupuestoLenguaje $presupuesto,
	) {
	}

	public function analizar( string $tema, string $comentarioTexto ): ?AprendizajeAudiencia {
		if ( ! $this->presupuesto->disponible() ) {
			return null;
		}

		try {
			return $this->analizarConProveedor( $tema, $comentarioTexto );
		} catch ( ProveedorLenguajeException | DecisionEditorialException $excepcion ) {
			return null;
		}
	}

	/**
	 * @throws ProveedorLenguajeException
	 * @throws DecisionEditorialException
	 */
	private function analizarConProveedor( string $tema, string $comentarioTexto ): AprendizajeAudiencia {
		$directrices = implode(
			"\n",
			array(
				'Eres el analista de audiencia de un medio digital. Te doy el tema de un artículo y un comentario real y sustantivo de un lector.',
				'Extrae un aprendizaje breve (una frase) sobre qué le importa a esta audiencia respecto al tema, y el sentimiento dominante del comentario.',
				'Responde ÚNICAMENTE con un objeto JSON, sin texto adicional, con esta forma exacta:',
				'{"resumen": string (una frase corta), "sentimiento": "positivo" o "negativo" o "mixto" o "neutral"}',
			)
		);

		$material = sprintf( "Tema del artículo: %s\nComentario del lector: %s", $tema, $comentarioTexto );

		$peticion  = new PeticionLenguaje( PropositoLenguaje::AnalizarAudiencia, $directrices, $material, self::MAX_TOKENS_RESPUESTA );
		$respuesta = $this->proveedor->completar( $peticion );
		VerificadorTruncamiento::asegurar( $respuesta );
		$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );

		return $this->aAprendizaje( $datos );
	}

	/**
	 * @param array<string, mixed> $datos
	 *
	 * @throws DecisionEditorialException
	 */
	private function aAprendizaje( array $datos ): AprendizajeAudiencia {
		if ( ! isset( $datos['resumen'], $datos['sentimiento'] ) || ! is_string( $datos['resumen'] ) || ! is_string( $datos['sentimiento'] ) ) {
			throw new DecisionEditorialException( 'El análisis de audiencia no trae resumen y sentimiento.' );
		}

		$sentimiento = SentimientoAudiencia::tryFrom( $datos['sentimiento'] );

		if ( null === $sentimiento ) {
			throw new DecisionEditorialException( 'El análisis de audiencia usó un valor de sentimiento desconocido.' );
		}

		return new AprendizajeAudiencia( $datos['resumen'], $sentimiento );
	}
}
