<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PropositoLenguaje;
use Pluma\Redaccion\DecisionEditorialException;
use Pluma\Redaccion\ExtractorJsonLlm;
use Pluma\Redaccion\VerificadorTruncamiento;

/**
 * El "doble titular" (Libro Cap. 6.2): una sola llamada consolidada al
 * proveedor de lenguaje produce el titular SEO y la meta descripción juntos
 * (mismo espíritu de coste que `Pluma\Redaccion\CorrectorInterno` — nunca dos
 * llamadas donde una basta). `Pluma\Seo` es adyacente a `Pluma\Redaccion` en
 * la Ley de Arquitectura, así que reutiliza sus utilitarios de bajo nivel
 * (`ExtractorJsonLlm`, `VerificadorTruncamiento`) en vez de duplicarlos.
 */
final class GeneradorMetadatosSeo {

	private const MAX_TOKENS_RESPUESTA = 200;
	private const LONGITUD_TITULO      = 60;
	private const LONGITUD_DESCRIPCION = 155;

	public function __construct( private readonly LenguajeInterface $proveedor ) {
	}

	/**
	 * @throws SeoException
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException
	 */
	public function generar( string $tituloEditorial, string $tesis, string $palabraClavePrincipal ): MetadatosSeo {
		$directrices = implode(
			"\n",
			array(
				'Eres el Motor SEO de una redacción sintética. NO reescribes el argumento de la pieza: solo generas superficies orientadas a la búsqueda.',
				sprintf( 'Titular SEO: ≤%d caracteres, orientado a la búsqueda, debe incluir la palabra clave principal de forma natural (nunca forzada).', self::LONGITUD_TITULO ),
				sprintf( 'Meta descripción: ≤%d caracteres, vende el ángulo/tesis de la pieza — nunca un resumen genérico.', self::LONGITUD_DESCRIPCION ),
				'Responde ÚNICAMENTE con un objeto JSON de esta forma exacta: {"tituloSeo": string, "metaDescripcion": string}',
			)
		);

		$material = sprintf(
			"Titular editorial: %s\nTesis: %s\nPalabra clave principal: %s",
			$tituloEditorial,
			$tesis,
			$palabraClavePrincipal
		);

		$peticion = new PeticionLenguaje( PropositoLenguaje::Titulares, $directrices, $material, self::MAX_TOKENS_RESPUESTA );

		try {
			$respuesta = $this->proveedor->completar( $peticion );
			VerificadorTruncamiento::asegurar( $respuesta );
			$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );
		} catch ( DecisionEditorialException $error ) {
			$mensaje = $error->getMessage();
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje de excepción interno, nunca se imprime como HTML.
			throw new SeoException( $mensaje, 0, $error );
		}

		if ( ! isset( $datos['tituloSeo'], $datos['metaDescripcion'] ) || ! is_string( $datos['tituloSeo'] ) || ! is_string( $datos['metaDescripcion'] ) ) {
			throw new SeoException( 'El Motor SEO no recibió tituloSeo y metaDescripcion del proveedor de lenguaje.' );
		}

		return new MetadatosSeo(
			$this->truncar( $datos['tituloSeo'], self::LONGITUD_TITULO ),
			$this->truncar( $datos['metaDescripcion'], self::LONGITUD_DESCRIPCION )
		);
	}

	/**
	 * Cinturón y tirantes ante un modelo que ignore el límite indicado en las
	 * directrices: nunca se persiste una superficie SEO más larga que lo que
	 * el buscador realmente muestra.
	 */
	private function truncar( string $texto, int $longitudMaxima ): string {
		if ( mb_strlen( $texto ) <= $longitudMaxima ) {
			return $texto;
		}

		return rtrim( mb_substr( $texto, 0, $longitudMaxima - 1 ) ) . '…';
	}
}
