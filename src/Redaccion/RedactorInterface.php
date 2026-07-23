<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Investigacion\Expediente;

/**
 * Contrato de redacción. `RedactorMecanico` (Etapa 1) no invoca
 * `LenguajeInterface` — el periodista sintético con diales, matriz de
 * tonos y Corrector Interno llega en la Etapa 2 (Libro Cap. 5).
 */
interface RedactorInterface {

	public function redactar( Expediente $expediente ): BorradorMecanico;
}
