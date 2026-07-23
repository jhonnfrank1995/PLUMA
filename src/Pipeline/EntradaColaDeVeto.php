<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use DateTimeImmutable;

/**
 * Una pieza en la cola de veto del modo Copiloto (Libro Cap. 10.2: "la cola
 * de veto con cuenta regresiva").
 */
final readonly class EntradaColaDeVeto {

	public function __construct(
		public Pieza $pieza,
		public RanuraPublicacion $ranura,
		public DateTimeImmutable $horaLimiteVeto,
	) {
	}
}
