<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * El "doble titular" (Libro Cap. 6.2): `tituloSeo` es la etiqueta `<title>`,
 * ≤60 caracteres, orientada a la búsqueda — distinta del titular editorial
 * que ya redactó `Pluma\Redaccion` con la voz del periodista.
 * `metaDescripcion` (≤155 car.) vende el ángulo de la pieza, no la resume.
 */
final readonly class MetadatosSeo {

	public function __construct(
		public string $tituloSeo,
		public string $metaDescripcion,
	) {
	}

	/**
	 * @return array{tituloSeo: string, metaDescripcion: string}
	 */
	public function aArray(): array {
		return array(
			'tituloSeo'       => $this->tituloSeo,
			'metaDescripcion' => $this->metaDescripcion,
		);
	}

	/**
	 * @param array{tituloSeo: string, metaDescripcion: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['tituloSeo'], $datos['metaDescripcion'] );
	}
}
