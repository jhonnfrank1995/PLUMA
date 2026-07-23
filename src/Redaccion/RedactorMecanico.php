<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

use Pluma\Investigacion\Expediente;
use Pluma\Investigacion\HechoFuente;

/**
 * Construye el borrador directamente de los hechos del expediente. El
 * contenido de las fuentes es un dato externo no confiable (GOVERNANCE
 * §3.4/pl-wp-core §1): se escapa igual que cualquier salida, nunca se
 * interpreta como HTML/instrucciones.
 */
final class RedactorMecanico implements RedactorInterface {

	public function redactar( Expediente $expediente ): BorradorMecanico {
		$titulo = ucfirst( $expediente->tendenciaOrigen );

		$items = array_map( array( $this, 'itemDeHecho' ), $expediente->hechos );

		$cuerpo = sprintf(
			'<p>%s</p><ul>%s</ul><p><em>%s</em></p>',
			sprintf(
				/* translators: %s: término de la tendencia detectada. */
				esc_html__( 'Borrador generado automáticamente a partir de la tendencia "%s".', 'pluma-engine' ),
				esc_html( $expediente->tendenciaOrigen )
			),
			implode( '', $items ),
			esc_html__(
				'Este borrador es mecánico (Etapa 1): no pasó por un periodista sintético ni por las compuertas de calidad, riesgo u originalidad. Requiere revisión editorial completa antes de publicarse.',
				'pluma-engine'
			)
		);

		return new BorradorMecanico( $titulo, $cuerpo );
	}

	private function itemDeHecho( HechoFuente $hecho ): string {
		$host = wp_parse_url( $hecho->url, PHP_URL_HOST );
		$host = is_string( $host ) ? $host : $hecho->url;

		return sprintf(
			'<li>%s (<a href="%s">%s</a>)</li>',
			esc_html( $hecho->extracto ),
			esc_url( $hecho->url ),
			esc_html( $host )
		);
	}
}
