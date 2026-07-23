<?php

declare(strict_types=1);

namespace Pluma\Seo;

/**
 * Un candidato de enlazado interno (Libro Cap. 6.2): "2–5 enlaces a piezas
 * propias relacionadas". La inserción real del ancla dentro del cuerpo HTML
 * queda fuera de esta Etapa (ver `docs/deuda.md`) — este DTO es la lista de
 * candidatos que un futuro bloque "piezas relacionadas" o un editor humano
 * consume.
 */
final readonly class EnlaceInterno {

	public function __construct(
		public int $postId,
		public string $url,
		public string $titulo,
	) {
	}

	/**
	 * @return array{postId: int, url: string, titulo: string}
	 */
	public function aArray(): array {
		return array(
			'postId' => $this->postId,
			'url'    => $this->url,
			'titulo' => $this->titulo,
		);
	}

	/**
	 * @param array{postId: int, url: string, titulo: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['postId'], $datos['url'], $datos['titulo'] );
	}
}
