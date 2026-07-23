<?php

declare(strict_types=1);

namespace Pluma\Datos;

use DateTimeImmutable;
use Pluma\Taxonomia\EntradaVocabulario;
use Pluma\Taxonomia\TipoVocabulario;

/**
 * Contrato del repositorio de vocabulario (Libro Cap. 7.1-7.2): categorías
 * (fijas, el Taxónomo jamás crea) y etiquetas (dinámicas, con cuarentena).
 */
interface RepositorioVocabularioInterface {

	/**
	 * @param list<string> $alias
	 */
	public function crear(
		TipoVocabulario $tipo,
		string $nombre,
		string $slug,
		array $alias,
		bool $enCuarentena,
		DateTimeImmutable $ahora
	): int;

	public function obtenerPorTipoYSlug( TipoVocabulario $tipo, string $slug ): ?EntradaVocabulario;

	/**
	 * @return list<EntradaVocabulario>
	 */
	public function obtenerPorTipo( TipoVocabulario $tipo ): array;

	/**
	 * Reconciliación (Libro Cap. 7.2, punto 2): "si existe, se reutiliza —
	 * siempre". Incrementa el contador de uso, base del umbral de cuarentena
	 * (3+ piezas, Cap. 7.2 punto 3).
	 */
	public function incrementarUso( int $id, DateTimeImmutable $ahora ): bool;

	public function salirDeCuarentena( int $id, DateTimeImmutable $ahora ): bool;
}
