<?php

declare(strict_types=1);

namespace Pluma\Sensores;

use Pluma\Proveedores\LenguajeInterface;
use Pluma\Proveedores\PeticionLenguaje;
use Pluma\Proveedores\PresupuestoLenguaje;
use Pluma\Proveedores\ProveedorLenguajeException;
use Pluma\Proveedores\PropositoLenguaje;

/**
 * Huella semántica del Radar (Libro Cap. 3.4): ¿la tendencia nueva es la
 * misma historia que una ya procesada bajo otro titular, la evolución de
 * una historia ya cubierta ("dos golpes"), o no tiene relación? Modelo
 * económico y determinista (`PropositoLenguaje::CompararHistorias`), mismo
 * patrón que `Pluma\Redaccion\ClasificadorNoticia`.
 *
 * **Fail-safe obligatorio**: sin presupuesto, con el proveedor caído, o con
 * una respuesta no interpretable, `comparar()` devuelve `SinRelacion` en vez
 * de lanzar — un fallo aquí jamás debe bloquear el tick del Orquestador
 * (mismo espíritu que "un Sensor caído no detiene el resto del pipeline").
 */
final class ComparadorHistorias {

	private const MAX_TOKENS_RESPUESTA = 200;
	private const MAX_CANDIDATAS       = 20;

	public function __construct(
		private readonly LenguajeInterface $proveedor,
		private readonly PresupuestoLenguaje $presupuesto,
	) {
	}

	/**
	 * @param list<array{id: int, termino: string, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>}> $candidatas
	 */
	public function comparar( TendenciaDetectada $nueva, array $candidatas ): ResultadoComparacionHistorias {
		if ( array() === $candidatas || ! $this->presupuesto->disponible() ) {
			return new ResultadoComparacionHistorias( RelacionHistoria::SinRelacion, null );
		}

		try {
			return $this->compararConProveedor( $nueva, array_slice( $candidatas, 0, self::MAX_CANDIDATAS ) );
		} catch ( ProveedorLenguajeException | ComparadorHistoriasException $excepcion ) {
			return new ResultadoComparacionHistorias( RelacionHistoria::SinRelacion, null );
		}
	}

	/**
	 * @param list<array{id: int, termino: string, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>}> $candidatas
	 *
	 * @throws ProveedorLenguajeException
	 * @throws ComparadorHistoriasException
	 */
	private function compararConProveedor( TendenciaDetectada $nueva, array $candidatas ): ResultadoComparacionHistorias {
		$directrices = implode(
			"\n",
			array(
				'Eres el editor de guardia de un medio digital. Te doy una tendencia nueva detectada por el radar y una lista numerada de historias que el medio ya procesó recientemente.',
				'Decide la relación entre la tendencia nueva y la lista: "identica" (es la misma historia bajo otro titular, no aporta nada nuevo), "evoluciona" (es la evolución de una historia de la lista: nuevo dato, giro o desmentido), o "sin_relacion" (no tiene relación con ninguna).',
				'Responde ÚNICAMENTE con un objeto JSON, sin texto adicional, con esta forma exacta:',
				'{"relacion": "identica" o "evoluciona" o "sin_relacion", "candidatoId": integer (el id de la historia de la lista relacionada) o null si "sin_relacion"}',
			)
		);

		$peticion = new PeticionLenguaje(
			PropositoLenguaje::CompararHistorias,
			$directrices,
			$this->material( $nueva, $candidatas ),
			self::MAX_TOKENS_RESPUESTA
		);

		$respuesta = $this->proveedor->completar( $peticion );
		VerificadorTruncamiento::asegurar( $respuesta );
		$datos = ExtractorJsonLlm::extraer( $respuesta->contenido );

		return $this->aResultado( $datos, $candidatas );
	}

	/**
	 * @param list<array{id: int, termino: string, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>}> $candidatas
	 */
	private function material( TendenciaDetectada $nueva, array $candidatas ): string {
		$lineas   = array();
		$lineas[] = 'TENDENCIA NUEVA: ' . $nueva->termino;

		foreach ( $nueva->articulosRelacionados as $articulo ) {
			$lineas[] = '  - ' . $articulo['titulo'];
		}

		$lineas[] = '';
		$lineas[] = 'HISTORIAS YA PROCESADAS:';

		foreach ( $candidatas as $candidata ) {
			$lineas[] = 'id ' . $candidata['id'] . ': ' . $candidata['termino'];

			foreach ( $candidata['articulosRelacionados'] as $articulo ) {
				$lineas[] = '  - ' . $articulo['titulo'];
			}
		}

		return implode( "\n", $lineas );
	}

	/**
	 * @param array<string, mixed>                                                                                                    $datos
	 * @param list<array{id: int, termino: string, articulosRelacionados: list<array{titulo: string, url: string, fuente: string}>}> $candidatas
	 *
	 * @throws ComparadorHistoriasException
	 */
	private function aResultado( array $datos, array $candidatas ): ResultadoComparacionHistorias {
		if ( ! isset( $datos['relacion'] ) || ! is_string( $datos['relacion'] ) ) {
			throw new ComparadorHistoriasException( 'La comparación del proveedor de lenguaje no trae una relación válida.' );
		}

		$relacion = RelacionHistoria::tryFrom( $datos['relacion'] );

		if ( null === $relacion ) {
			throw new ComparadorHistoriasException( 'La comparación del proveedor de lenguaje usó un valor de relación desconocido.' );
		}

		if ( RelacionHistoria::SinRelacion === $relacion ) {
			return new ResultadoComparacionHistorias( RelacionHistoria::SinRelacion, null );
		}

		$candidatoId = $datos['candidatoId'] ?? null;

		if ( ! is_numeric( $candidatoId ) ) {
			return new ResultadoComparacionHistorias( RelacionHistoria::SinRelacion, null );
		}

		$idValido = false;

		foreach ( $candidatas as $candidata ) {
			if ( $candidata['id'] === (int) $candidatoId ) {
				$idValido = true;
			}
		}

		if ( ! $idValido ) {
			return new ResultadoComparacionHistorias( RelacionHistoria::SinRelacion, null );
		}

		return new ResultadoComparacionHistorias( $relacion, (int) $candidatoId );
	}
}
