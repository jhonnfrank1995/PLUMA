<?php

declare(strict_types=1);

namespace Pluma\Seo;

use Pluma\Investigacion\Expediente;
use Pluma\Redaccion\FichaDecisionEditorial;

/**
 * Único punto de entrada de `Pluma\Seo` (Libro Cap. 6): "tomar una pieza
 * editorial ya excelente y vestirla para competir en el SERP sin
 * deformarla". Cero mutación de argumentos — solo calcula las superficies;
 * quien la invoca decide cuándo escribirlas (postmeta del plugin detectado,
 * bloque de enlaces relacionados, JSON-LD en el render del frontend).
 */
final class MotorSeo {

	public function __construct(
		private readonly ExtractorPalabrasClave $extractorPalabrasClave,
		private readonly GeneradorMetadatosSeo $generadorMetadatos,
		private readonly DetectorPluginSeo $detectorPlugin,
		private readonly EnlazadorInterno $enlazadorInterno,
		private readonly AuditorCanibalizacion $auditorCanibalizacion,
	) {
	}

	/**
	 * @throws SeoException
	 * @throws \Pluma\Proveedores\ProveedorLenguajeException
	 */
	public function optimizar(
		int $piezaId,
		Expediente $expediente,
		FichaDecisionEditorial $ficha,
		string $tituloEditorial
	): DatosSeo {
		$palabrasClave = $this->extractorPalabrasClave->extraer( $expediente );
		$tesis         = $ficha->tesisElegida()->tesis;
		$metadatos     = $this->generadorMetadatos->generar( $tituloEditorial, $tesis, $palabrasClave->principal );
		$tipoEsquema   = TipoEsquemaArticulo::desdeTono( $ficha->tonoDominante );

		$enlaces = $this->enlazadorInterno->sugerir( $ficha->periodistaId, $ficha->clasificacion->tema, $piezaId );

		return new DatosSeo(
			$palabrasClave,
			$metadatos,
			$tipoEsquema,
			$this->detectorPlugin->detectar(),
			$enlaces,
			$this->auditorCanibalizacion->hayCanibalizacion( $palabrasClave->principal, $piezaId )
		);
	}
}
