<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

use DateTimeImmutable;

/**
 * Una entrada del vocabulario del sitio (Libro Cap. 7.1-7.2): una categoría
 * fija o una etiqueta dinámica, con sus alias conocidos ("IA" =
 * "inteligencia artificial") y su estado de cuarentena.
 */
final readonly class EntradaVocabulario {

	/**
	 * @param list<string> $alias
	 */
	public function __construct(
		public int $id,
		public TipoVocabulario $tipo,
		public string $nombre,
		public string $slug,
		public array $alias,
		public bool $enCuarentena,
		public int $vecesUsada,
		public DateTimeImmutable $creadoEn,
		public DateTimeImmutable $actualizadoEn,
	) {
	}
}
