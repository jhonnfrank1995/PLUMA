<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Pipeline\EstadoPieza;

/**
 * Notificación por correo de la Sala de Revisión (Libro Cap. 10.2:
 * "notificaciones por correo/Telegram/Slack"; decisión del propietario:
 * solo correo por ahora vía `wp_mail`). Se engancha al evento que
 * `Transicionador` ya dispara en TODA transición (CLAUDE.md § Ley de
 * Arquitectura: "toda transición de estado dispara evento
 * pluma/pieza_{estado}") — cero acoplamiento directo con el Orquestador.
 */
final class NotificadorRevision {

	public function registrar(): void {
		add_action( 'pluma/pieza_retenida', array( $this, 'notificarRetenida' ), 10, 3 );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- la firma debe calzar con la de `do_action('pluma/pieza_retenida', ...)`; el estado anterior no aporta nada al correo.
	public function notificarRetenida( int $piezaId, EstadoPieza $estadoAnterior, string $motivo ): void {
		$destinatario = get_option( 'admin_email' );

		if ( ! is_string( $destinatario ) || '' === $destinatario ) {
			return;
		}

		$asunto = sprintf(
			/* translators: %d: id de la Pieza */
			__( 'PLUMA: la pieza #%d necesita tu revisión', 'pluma-engine' ),
			$piezaId
		);

		$cuerpo = sprintf(
			/* translators: 1: id de la Pieza, 2: motivo de la retención */
			__( "La pieza #%1\$d fue retenida y espera tu decisión en la Sala de Revisión.\n\nMotivo: %2\$s", 'pluma-engine' ),
			$piezaId,
			$motivo
		);

		wp_mail( $destinatario, $asunto, $cuerpo );
	}
}
