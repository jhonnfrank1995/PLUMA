<?php

declare(strict_types=1);

namespace Pluma\Sensores;

/**
 * Estado de una tendencia en el radar (Libro Cap. 10.2 — Sala de Tendencias,
 * decisión de producto del propietario, 2026-07-23):
 *
 * - EN_PIPELINE: la agenda automática — la Pieza creada al detectarse avanza.
 * - IGNORADA: el editor la sacó de la agenda; su Pieza se descarta y la
 *   tarjeta desaparece de la Sala (la deduplicación evita que reingrese).
 * - VIGILADA: "observar sin cubrir todavía" — su Pieza se descarta (no se
 *   gasta investigación/redacción/APIs) pero la tendencia sigue visible y
 *   destacada en la Sala; "Cubrir ahora" crea una Pieza nueva con prioridad.
 */
enum EstadoTendencia: string {

	case EnPipeline = 'en_pipeline';
	case Ignorada   = 'ignorada';
	case Vigilada   = 'vigilada';
}
