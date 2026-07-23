<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

/**
 * Grafo de estados de la Pieza (pl-pipeline, `references/estados.md`).
 *
 * DETECTADA → EN_INVESTIGACION → INVESTIGADA → EN_REDACCION → REDACTADA →
 * OPTIMIZADA → EN_REVISION → APROBADA → PROGRAMADA → PUBLICADA, con salidas
 * laterales RETENIDA/DESCARTADA/FALLIDA desde cualquier estado no terminal.
 */
enum EstadoPieza: string {

	case Detectada       = 'detectada';
	case EnInvestigacion = 'en_investigacion';
	case Investigada     = 'investigada';
	case EnRedaccion     = 'en_redaccion';
	case Redactada       = 'redactada';
	case Optimizada      = 'optimizada';
	case EnRevision      = 'en_revision';
	case Aprobada        = 'aprobada';
	case Programada      = 'programada';
	case Publicada       = 'publicada';
	case Retenida        = 'retenida';
	case Descartada      = 'descartada';
	case Fallida         = 'fallida';

	public function esTerminal(): bool {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext -- falso positivo: $this en un método de enum (PHP 8.1) es válido; el sniff aún no reconoce enums.
		return match ( $this ) {
			self::Publicada, self::Descartada => true,
			default => false,
		};
	}
}
