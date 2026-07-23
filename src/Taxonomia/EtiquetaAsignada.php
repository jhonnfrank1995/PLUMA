<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

/**
 * Una etiqueta asignada a la Pieza por `GestorEtiquetas` (Libro Cap. 7.2):
 * ya sea reutilizada del vocabulario existente o recién creada (en
 * cuyo caso nace en cuarentena — punto 3).
 */
final readonly class EtiquetaAsignada {

	public function __construct(
		public int $vocabularioId,
		public string $nombre,
		public bool $esNueva,
		public bool $enCuarentena,
	) {
	}

	/**
	 * @return array{vocabularioId: int, nombre: string, esNueva: bool, enCuarentena: bool}
	 */
	public function aArray(): array {
		return array(
			'vocabularioId' => $this->vocabularioId,
			'nombre'        => $this->nombre,
			'esNueva'       => $this->esNueva,
			'enCuarentena'  => $this->enCuarentena,
		);
	}

	/**
	 * @param array{vocabularioId: int, nombre: string, esNueva: bool, enCuarentena: bool} $datos
	 */
	public static function desdeArray( array $datos ): self {
		return new self( $datos['vocabularioId'], $datos['nombre'], $datos['esNueva'], $datos['enCuarentena'] );
	}
}
