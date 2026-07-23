<?php

declare(strict_types=1);

namespace Pluma\Redaccion;

/**
 * Nivel de sátira que permite un tipo de noticia (Libro Cap. 5.3, columna
 * "Sátira permitida" de la matriz de tonos). `Bloqueada` es la única regla de
 * sistema inviolable: {@see MatrizTonos::filaSistemaTragedia()} la impone
 * sobre cualquier configuración del periodista, sin excepción.
 */
enum NivelSatiraPermitida: string {

	case Bloqueada     = 'bloqueada';
	case No            = 'no';
	case ConModeracion = 'con_moderacion';
	case EnRemate      = 'en_remate';
	case PiezaCompleta = 'pieza_completa';
}
