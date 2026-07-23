<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Una fila de la matriz de tonos (Libro Cap. 5.3): tipo de noticia → tono
 * dominante, tono de apoyo, nivel de sátira permitida.
 */
final readonly class EntradaMatrizTono {

	public function __construct(
		public TipoNoticia $tipoNoticia,
		public Tono $tonoDominante,
		public Tono $tonoApoyo,
		public NivelSatiraPermitida $nivelSatira,
	) {
	}

	/**
	 * @return array{tipoNoticia: string, tonoDominante: string, tonoApoyo: string, nivelSatira: string}
	 */
	public function aArray(): array {
		return array(
			'tipoNoticia'   => $this->tipoNoticia->value,
			'tonoDominante' => $this->tonoDominante->value,
			'tonoApoyo'     => $this->tonoApoyo->value,
			'nivelSatira'   => $this->nivelSatira->value,
		);
	}

	/**
	 * @param array{tipoNoticia: string, tonoDominante: string, tonoApoyo: string, nivelSatira: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			TipoNoticia::from( $datos['tipoNoticia'] ),
			Tono::from( $datos['tonoDominante'] ),
			Tono::from( $datos['tonoApoyo'] ),
			NivelSatiraPermitida::from( $datos['nivelSatira'] )
		);
	}
}
