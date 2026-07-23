<?php

declare(strict_types=1);

namespace Pluma\Taxonomia;

/**
 * Las dos ramas del vocabulario del sitio (Libro Cap. 7.1): categorías
 * (arquitectura fija del sitio, el Taxónomo jamás las crea) y etiquetas
 * (dinámicas, el Taxónomo las crea con umbral y cuarentena).
 */
enum TipoVocabulario: string {

	case Categoria = 'categoria';
	case Etiqueta  = 'etiqueta';
}
