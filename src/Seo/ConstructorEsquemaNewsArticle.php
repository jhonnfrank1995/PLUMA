<?php

declare(strict_types=1);

namespace Pluma\Seo;

use DateTimeImmutable;

/**
 * Construye el documento JSON-LD de la pieza (Libro Cap. 6.2). Propiedades
 * verificadas contra la guía oficial de Google para datos estructurados de
 * Article/NewsArticle (`headline`, `image`, `datePublished`, `dateModified`,
 * `author`) y contra las propiedades estándar de `Article` en schema.org
 * (`publisher`, `mainEntityOfPage`) — ninguna se inventó.
 *
 * Función pura: no toca `$wpdb`, no llama WordPress. Quien la invoca (la
 * plantilla de render en el frontend) es responsable de resolver los datos
 * reales del post (permalink, fechas, autor) antes de pasarlos aquí.
 */
final class ConstructorEsquemaNewsArticle {

	/**
	 * @param list<string> $urlsImagenes
	 * @return array<string, mixed>
	 */
	public function construir(
		TipoEsquemaArticulo $tipo,
		string $titular,
		array $urlsImagenes,
		DateTimeImmutable $fechaPublicacion,
		DateTimeImmutable $fechaModificacion,
		string $nombreAutor,
		?string $urlPerfilAutor,
		string $nombreSitio,
		?string $urlLogoSitio,
		string $urlPieza
	): array {
		$autor = array(
			'@type' => 'Person',
			'name'  => $nombreAutor,
		);

		if ( null !== $urlPerfilAutor ) {
			$autor['url'] = $urlPerfilAutor;
		}

		$publisher = array(
			'@type' => 'Organization',
			'name'  => $nombreSitio,
		);

		if ( null !== $urlLogoSitio ) {
			$publisher['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $urlLogoSitio,
			);
		}

		$documento = array(
			'@context'         => 'https://schema.org',
			'@type'            => $tipo->value,
			'headline'         => $titular,
			'datePublished'    => $fechaPublicacion->format( DateTimeImmutable::ATOM ),
			'dateModified'     => $fechaModificacion->format( DateTimeImmutable::ATOM ),
			'author'           => $autor,
			'publisher'        => $publisher,
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $urlPieza,
			),
		);

		if ( array() !== $urlsImagenes ) {
			$documento['image'] = $urlsImagenes;
		}

		return $documento;
	}
}
