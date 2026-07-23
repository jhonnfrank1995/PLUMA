<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Investigacion\Expediente;
use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PropositoLenguaje;

/**
 * Paso 1 del Algoritmo de Decisión Editorial (Libro Cap. 5.5): clasifica el
 * expediente en cinco ejes con un modelo económico (`PropositoLenguaje::Clasificar`).
 */
final class ClasificadorNoticia {

	private const MAX_TOKENS_RESPUESTA = 400;

	public function __construct( private readonly LenguajeInterface $proveedor ) {
	}

	/**
	 * @throws DecisionEditorialException si la respuesta no trae los cinco ejes con valores válidos.
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException
	 */
	public function clasificar( Expediente $expediente ): ClasificacionNoticia {
		$directrices = implode(
			"\n",
			array(
				'Eres el editor de guardia de un medio digital. Clasifica el expediente adjunto en exactamente cinco ejes.',
				'Responde ÚNICAMENTE con un objeto JSON, sin texto adicional, con esta forma exacta:',
				'{"tema": string (vertical temático, ej. "economia"), '
					. '"gravedad": integer 0-100 (0 = viral ligero, 100 = tragedia), '
					. '"polaridad": string (quiénes son los actores y qué está en disputa), '
					. '"novedad": "primicia" o "historia_en_evolucion", '
					. '"potencialConversacional": integer 0-100, '
					. '"tipoNoticia": uno de "anuncio_corporativo", "escandalo_politico", "tragedia", "cultura_viral", "dato_economico"}',
			)
		);

		$peticion = new PeticionLenguaje(
			PropositoLenguaje::Clasificar,
			$directrices,
			FormateadorExpediente::comoTexto( $expediente ),
			self::MAX_TOKENS_RESPUESTA
		);

		$respuesta = $this->proveedor->completar( $peticion );
		VerificadorTruncamiento::asegurar( $respuesta );
		$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );

		return $this->aClasificacion( $datos );
	}

	/**
	 * @param array<string, mixed> $datos
	 */
	private function aClasificacion( array $datos ): ClasificacionNoticia {
		if (
			! isset( $datos['tema'], $datos['gravedad'], $datos['polaridad'], $datos['novedad'], $datos['potencialConversacional'], $datos['tipoNoticia'] )
			|| ! is_string( $datos['tema'] )
			|| ! is_numeric( $datos['gravedad'] )
			|| ! is_string( $datos['polaridad'] )
			|| ! is_string( $datos['novedad'] )
			|| ! is_numeric( $datos['potencialConversacional'] )
			|| ! is_string( $datos['tipoNoticia'] )
		) {
			throw new DecisionEditorialException( 'La clasificación del proveedor de lenguaje no trae los cinco ejes esperados.' );
		}

		$novedad     = NovedadNoticia::tryFrom( $datos['novedad'] );
		$tipoNoticia = TipoNoticia::tryFrom( $datos['tipoNoticia'] );

		if ( null === $novedad || null === $tipoNoticia ) {
			throw new DecisionEditorialException( 'La clasificación del proveedor de lenguaje usó un valor de enum desconocido.' );
		}

		return new ClasificacionNoticia(
			$datos['tema'],
			max( 0, min( 100, (int) $datos['gravedad'] ) ),
			$datos['polaridad'],
			$novedad,
			max( 0, min( 100, (int) $datos['potencialConversacional'] ) ),
			$tipoNoticia
		);
	}
}
