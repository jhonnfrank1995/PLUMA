<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

/**
 * Resultado consolidado de `Taxonomo` (Libro Cap. 7): la categoría asignada
 * (`null` si ninguna categoría existente alcanzó el umbral de similitud —
 * el Taxónomo JAMÁS crea una) y las 3-6 etiquetas asignadas.
 */
final readonly class ResultadoTaxonomia {

	/**
	 * @param list<EtiquetaAsignada> $etiquetas
	 */
	public function __construct(
		public ?string $categoriaAsignada,
		public array $etiquetas,
	) {
	}

	/**
	 * @return array{categoriaAsignada: ?string, etiquetas: list<array{vocabularioId: int, nombre: string, esNueva: bool, enCuarentena: bool}>}
	 */
	public function aArray(): array {
		return array(
			'categoriaAsignada' => $this->categoriaAsignada,
			'etiquetas'         => array_map( static fn ( EtiquetaAsignada $e ): array => $e->aArray(), $this->etiquetas ),
		);
	}

	/**
	 * @param array{categoriaAsignada: ?string, etiquetas: list<array{vocabularioId: int, nombre: string, esNueva: bool, enCuarentena: bool}>} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['categoriaAsignada'],
			array_map( static fn ( array $e ): EtiquetaAsignada => EtiquetaAsignada::desdeArray( $e ), $datos['etiquetas'] )
		);
	}
}
