<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * Resultado consolidado de `MotorSeo` (Libro Cap. 6.2-6.3): lo que se
 * persiste en el expediente de la Pieza para que la Sala de Revisión pueda
 * mostrar qué superficies SEO se generaron y en qué campos de qué plugin
 * (o propios) se escribirán al publicar.
 */
final readonly class DatosSeo {

	/**
	 * @param list<EnlaceInterno> $enlacesInternos
	 */
	public function __construct(
		public PalabrasClave $palabrasClave,
		public MetadatosSeo $metadatos,
		public TipoEsquemaArticulo $tipoEsquema,
		public TipoPluginSeo $pluginDetectado,
		public array $enlacesInternos,
		public bool $canibalizacionDetectada,
	) {
	}

	/**
	 * @return array{palabrasClave: array{principal: string, secundarias: list<string>}, metadatos: array{tituloSeo: string, metaDescripcion: string}, tipoEsquema: string, pluginDetectado: string, enlacesInternos: list<array{postId: int, url: string, titulo: string}>, canibalizacionDetectada: bool}
	 */
	public function aArray(): array {
		return array(
			'palabrasClave'           => $this->palabrasClave->aArray(),
			'metadatos'               => $this->metadatos->aArray(),
			'tipoEsquema'             => $this->tipoEsquema->value,
			'pluginDetectado'         => $this->pluginDetectado->value,
			'enlacesInternos'         => array_map( static fn ( EnlaceInterno $e ): array => $e->aArray(), $this->enlacesInternos ),
			'canibalizacionDetectada' => $this->canibalizacionDetectada,
		);
	}

	/**
	 * @param array{palabrasClave: array{principal: string, secundarias: list<string>}, metadatos: array{tituloSeo: string, metaDescripcion: string}, tipoEsquema: string, pluginDetectado: string, enlacesInternos: list<array{postId: int, url: string, titulo: string}>, canibalizacionDetectada: bool} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			PalabrasClave::desdeArray( $datos['palabrasClave'] ),
			MetadatosSeo::desdeArray( $datos['metadatos'] ),
			TipoEsquemaArticulo::from( $datos['tipoEsquema'] ),
			TipoPluginSeo::from( $datos['pluginDetectado'] ),
			array_map( static fn ( array $e ): EnlaceInterno => EnlaceInterno::desdeArray( $e ), $datos['enlacesInternos'] ),
			$datos['canibalizacionDetectada']
		);
	}
}
