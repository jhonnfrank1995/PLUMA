<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Ejes de clasificación de la matriz de tonos (Libro Cap. 5.3, tabla de
 * ejemplo de "Valentina"). El Paso 1 del Algoritmo de Decisión Editorial
 * clasifica cada Pieza en uno de estos tipos antes de consultar la matriz.
 */
enum TipoNoticia: string {

	case AnuncioCorporativo = 'anuncio_corporativo';
	case EscandaloPolitico  = 'escandalo_politico';
	case Tragedia           = 'tragedia';
	case CulturaViral       = 'cultura_viral';
	case DatoEconomico      = 'dato_economico';
}
