<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Salida de `RedactorSintetico::redactar()`. Si `retenida` es `true`, el
 * Corrector Interno no aprobó la pieza tras el máximo de ciclos (Libro
 * Cap. 5.6): `titulo`/`cuerpoHtml` quedan vacíos y el llamador debe mover la
 * Pieza a `EstadoPieza::Retenida` para revisión humana, nunca publicar "lo
 * menos malo".
 */
final readonly class ResultadoRedaccion {

	public function __construct(
		public string $titulo,
		public string $cuerpoHtml,
		public bool $retenida,
		public ?string $motivoRetenida,
		public int $ciclosUsados,
	) {
	}
}
