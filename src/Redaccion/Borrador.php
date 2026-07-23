<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use DateTimeImmutable;

/**
 * Una versión de una Pieza con las anotaciones del Corrector Interno
 * (Libro Cap. 5.6, Cap. 11 — tabla `borradores`): el historial de revisión.
 */
final readonly class Borrador {

	/**
	 * @param list<AnotacionCorrector> $anotaciones
	 */
	public function __construct(
		public int $id,
		public int $piezaId,
		public int $numeroCiclo,
		public string $contenido,
		public array $anotaciones,
		public bool $aprobadoPorCorrector,
		public DateTimeImmutable $creadoEn,
	) {
	}

	/**
	 * ¿Este borrador refleja el fallo del último ciclo permitido? (Libro
	 * Cap. 5.6: "máximo 2 ciclos; si al tercero no pasa, RETENIDA").
	 */
	public function agotoLosCiclos( int $maximoCiclos ): bool {
		return ! $this->aprobadoPorCorrector && $this->numeroCiclo >= $maximoCiclos;
	}
}
