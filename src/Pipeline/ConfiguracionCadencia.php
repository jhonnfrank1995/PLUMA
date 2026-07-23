<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

/**
 * Configuración de cadencia (Libro Cap. 9.2), resuelta desde `wp_options`
 * por {@see LectorConfiguracionCadencia}. Cuota elástica: objetivo, mínimo
 * y máximo — "el editor decide si la cuota es rígida o elástica" fijando
 * mínimo = máximo si la quiere rígida.
 */
final readonly class ConfiguracionCadencia {

	/**
	 * @param list<VentanaPublicacion> $ventanas
	 * @param array<string, int> $topesPorVertical
	 * @param array<int, int> $topesPorPeriodista
	 */
	public function __construct(
		public int $cuotaObjetivo,
		public int $cuotaMinima,
		public int $cuotaMaxima,
		public array $ventanas,
		public int $separacionMinimaMinutos,
		public int $jitterMaximoMinutos,
		public array $topesPorVertical,
		public array $topesPorPeriodista,
	) {
	}
}
