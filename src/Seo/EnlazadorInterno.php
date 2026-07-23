<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Datos\RepositorioMemoriaEditorialInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Pipeline\EstadoPieza;

/**
 * Enlazado interno automático (Libro Cap. 6.2): "la memoria de cobertura del
 * periodista es la fuente perfecta" — reutiliza `TipoMemoria::Cobertura` de
 * `Pluma\Redaccion\MemoriaEditorial` en vez de inventar un motor de similitud
 * de texto nuevo. Solo enlaza a piezas propias ya PUBLICADAS con `postId`
 * real; una pieza en cualquier otro estado no tiene URL estable todavía.
 */
final class EnlazadorInterno {

	private const MINIMO_ENLACES = 2;
	private const MAXIMO_ENLACES = 5;

	public function __construct(
		private readonly RepositorioMemoriaEditorialInterface $repositorioMemoria,
		private readonly RepositorioPiezasInterface $repositorioPiezas,
	) {
	}

	/**
	 * @return list<EnlaceInterno>
	 */
	public function sugerir( int $periodistaId, string $tema, int $piezaActualId ): array {
		$enlaces = array();

		foreach ( $this->repositorioMemoria->obtenerPosturasPorTema( $periodistaId, $tema ) as $postura ) {
			if ( count( $enlaces ) >= self::MAXIMO_ENLACES ) {
				break;
			}

			if ( null === $postura->piezaId || $piezaActualId === $postura->piezaId ) {
				continue;
			}

			$candidato = $this->repositorioPiezas->obtenerPorId( $postura->piezaId );

			if ( null === $candidato || EstadoPieza::Publicada !== $candidato->estado || null === $candidato->postId ) {
				continue;
			}

			$permalink = get_permalink( $candidato->postId );

			if ( false === $permalink ) {
				continue;
			}

			$enlaces[] = new EnlaceInterno( $candidato->postId, $permalink, get_the_title( $candidato->postId ) );
		}

		return $enlaces;
	}

	/**
	 * Libro Cap. 6.2: "2-5 enlaces". Menos de {@see self::MINIMO_ENLACES} no
	 * es un error (la memoria de cobertura puede estar vacía para un tema
	 * nuevo) — el llamador decide si eso amerita una nota al editor.
	 *
	 * @param list<EnlaceInterno> $enlaces
	 */
	public function cumpleMinimo( array $enlaces ): bool {
		return count( $enlaces ) >= self::MINIMO_ENLACES;
	}
}
