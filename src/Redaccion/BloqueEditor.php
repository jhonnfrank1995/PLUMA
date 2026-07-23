<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Sello del editor (Libro Cap. 5.7, Fase 7): comentario en primera persona
 * del periodista firmante + pregunta dicotómica construida desde la
 * polaridad detectada. El compromiso de respuesta automatizado (borradores
 * de respuesta a comentarios) queda fuera de esta Etapa — ver docs/deuda.md.
 */
final readonly class BloqueEditor {

	public function __construct(
		public string $comentario,
		public string $pregunta,
	) {
	}

	public function comoHtml(): string {
		return sprintf(
			'<hr /><p><em>%s</em></p><p><strong>%s</strong></p>',
			esc_html( $this->comentario ),
			esc_html( $this->pregunta )
		);
	}
}
