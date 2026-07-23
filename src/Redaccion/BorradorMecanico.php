<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Salida de la redacción rudimentaria de Etapa 1: sin periodista sintético
 * (Cap. 5, Etapa 2), sin diales ni voz — un título y un cuerpo mecánicos,
 * cada afirmación trazable a un hecho real del expediente.
 */
final readonly class BorradorMecanico {

	public function __construct(
		public string $titulo,
		public string $cuerpoHtml,
	) {
	}
}
