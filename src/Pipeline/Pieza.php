<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use DateTimeImmutable;
use Pluma\Investigacion\Expediente;

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
		);
	}
}
