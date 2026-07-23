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
		/**
		 * Ciclo escrito a mano por un editor desde la Mesa Editorial (Libro
		 * Cap. 10.2), no por el Corrector Interno — distinción de auditoría:
		 * `aprobadoPorCorrector` en un ciclo manual significa "un humano lo
		 * dio por bueno", no que la IA lo revisó.
		 */
		public bool $editadoManualmente = false,
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
