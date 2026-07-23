<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use DateTimeImmutable;
use Pluma\Compuertas\ResultadoEvaluacion;
use Pluma\Investigacion\Expediente;
use Pluma\Redaccion\FichaDecisionEditorial;
use Pluma\Seo\DatosSeo;
use Pluma\Taxonomia\ResultadoTaxonomia;

/**
 * Instantánea inmutable de una Pieza (GOVERNANCE §1.2): cada lectura del
 * repositorio devuelve un objeto nuevo. La persistencia y las transiciones
 * las gestiona `Transicionador` + `Pluma\Datos\RepositorioPiezas`, jamás
 * mutando esta instancia.
 */
final readonly class Pieza {

	public function __construct(
		public int $id,
		public int $tendenciaId,
		public EstadoPieza $estado,
		public ?Expediente $expediente,
		public ?int $postId,
		public DateTimeImmutable $creadaEn,
		public DateTimeImmutable $actualizadaEn,
		public ?int $periodistaId = null,
		public ?int $periodistaVersionId = null,
		public ?FichaDecisionEditorial $fichaDecisionEditorial = null,
		public ?ResultadoEvaluacion $resultadoCompuertas = null,
		public ?DatosSeo $datosSeo = null,
		public ?ResultadoTaxonomia $resultadoTaxonomia = null,
	) {
	}

	public function conEstado( EstadoPieza $nuevoEstado, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$nuevoEstado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conExpediente( Expediente $expediente, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conPostId( int $postId, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conPeriodistaAsignado( int $periodistaId, int $periodistaVersionId, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$periodistaId,
			$periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conFichaDecisionEditorial( FichaDecisionEditorial $ficha, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$ficha,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conResultadoCompuertas( ResultadoEvaluacion $resultado, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$resultado,
			$this->datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conDatosSeo( DatosSeo $datosSeo, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$datosSeo,
			$this->resultadoTaxonomia,
		);
	}

	public function conResultadoTaxonomia( ResultadoTaxonomia $resultado, DateTimeImmutable $ahora ): self {
		return new self(
			$this->id,
			$this->tendenciaId,
			$this->estado,
			$this->expediente,
			$this->postId,
			$this->creadaEn,
			$ahora,
			$this->periodistaId,
			$this->periodistaVersionId,
			$this->fichaDecisionEditorial,
			$this->resultadoCompuertas,
			$this->datosSeo,
			$resultado,
		);
	}
}
