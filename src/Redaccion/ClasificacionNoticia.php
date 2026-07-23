<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Salida del Paso 1 del Algoritmo de Decisión Editorial (Libro Cap. 5.5):
 * el expediente se clasifica en cinco ejes antes de asignar periodista.
 * `tipoNoticia` es la clave de consulta contra la {@see MatrizTonos} del
 * periodista asignado (Cap. 5.3).
 */
final readonly class ClasificacionNoticia {

	public function __construct(
		public string $tema,
		public int $gravedad,
		public string $polaridad,
		public NovedadNoticia $novedad,
		public int $potencialConversacional,
		public TipoNoticia $tipoNoticia,
	) {
	}

	/**
	 * @return array{tema: string, gravedad: int, polaridad: string, novedad: string, potencialConversacional: int, tipoNoticia: string}
	 */
	public function aArray(): array {
		return array(
			'tema'                    => $this->tema,
			'gravedad'                => $this->gravedad,
			'polaridad'               => $this->polaridad,
			'novedad'                 => $this->novedad->value,
			'potencialConversacional' => $this->potencialConversacional,
			'tipoNoticia'             => $this->tipoNoticia->value,
		);
	}

	/**
	 * @param array{tema: string, gravedad: int, polaridad: string, novedad: string, potencialConversacional: int, tipoNoticia: string} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self(
			$datos['tema'],
			$datos['gravedad'],
			$datos['polaridad'],
			NovedadNoticia::from( $datos['novedad'] ),
			$datos['potencialConversacional'],
			TipoNoticia::from( $datos['tipoNoticia'] )
		);
	}
}
