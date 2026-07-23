<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Argumentos listos para `RepositorioPeriodistasInterface::crear()` (falta
 * solo el instante). Es lo que produce {@see PlantillasSiembra}: una
 * plantilla no es un periodista persistido, es la materia prima para crear uno.
 */
final readonly class PlantillaPeriodista {

	/**
	 * @param list<Especialidad> $especialidades
	 */
	public function __construct(
		public string $nombre,
		public ?string $avatarUrl,
		public string $biografia,
		public RolPeriodista $rol,
		public array $especialidades,
		public EstadoPeriodista $estado,
		public Diales $diales,
		public ReglasConducta $reglas,
		public MatrizTonos $matrizTonos,
	) {
	}
}
