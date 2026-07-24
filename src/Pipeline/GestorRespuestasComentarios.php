<?php

declare(strict_types=1);

namespace Pluma\Pipeline;

use Pluma\Datos\RepositorioPeriodistasInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioRespuestasComentariosInterface;
use Pluma\Kernel\RelojInterface;
use Pluma\Publicacion\PublicadorComentarioInterface;
use Pluma\Publicacion\PublicacionComentarioException;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Redaccion\RespuestaComentario;

/**
 * Sala de Comentarios (Libro Cap. 5.7, "el editor aprueba con un clic"):
 * las dos acciones humanas sobre un borrador de respuesta generado por el
 * Orquestador — Aprobar (publica el comentario real, autor invitado con el
 * nombre del periodista) o Descartar.
 */
final class GestorRespuestasComentarios {

	public function __construct(
		private readonly RepositorioRespuestasComentariosInterface $respuestas,
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioPeriodistasInterface $periodistas,
		private readonly PublicadorComentarioInterface $publicador,
		private readonly RelojInterface $reloj,
	) {
	}

	/**
	 * @return list<RespuestaComentario>
	 */
	public function obtenerPendientes( int $limite = 30 ): array {
		return $this->respuestas->obtenerPendientes( $limite );
	}

	/**
	 * @throws RespuestaComentarioNoEncontradaException
	 * @throws RespuestaComentarioEstadoInvalidoException
	 * @throws PublicacionComentarioException
	 */
	public function aprobar( int $respuestaId ): void {
		$respuesta = $this->obtenerPendiente( $respuestaId );

		$pieza      = $this->piezas->obtenerPorId( $respuesta->piezaId );
		$periodista = null !== $respuesta->periodistaId ? $this->periodistas->obtenerPorId( $respuesta->periodistaId ) : null;

		if ( null === $pieza || null === $pieza->postId || null === $periodista ) {
			$excepcion = new RespuestaComentarioEstadoInvalidoException( $respuestaId );

			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje interno construido por la propia excepción, sin entrada de usuario.
			throw $excepcion;
		}

		$comentarioRespuestaId = $this->publicador->publicar(
			$pieza->postId,
			$respuesta->comentarioId,
			$periodista->nombre,
			$respuesta->borrador ?? ''
		);

		$this->respuestas->marcarResuelta( $respuestaId, EstadoRespuestaComentario::Aprobado, $comentarioRespuestaId, $this->reloj->ahora() );
	}

	/**
	 * @throws RespuestaComentarioNoEncontradaException
	 * @throws RespuestaComentarioEstadoInvalidoException
	 */
	public function descartar( int $respuestaId ): void {
		$this->obtenerPendiente( $respuestaId );

		$this->respuestas->marcarResuelta( $respuestaId, EstadoRespuestaComentario::Descartado, null, $this->reloj->ahora() );
	}

	/**
	 * @throws RespuestaComentarioNoEncontradaException
	 * @throws RespuestaComentarioEstadoInvalidoException
	 */
	private function obtenerPendiente( int $respuestaId ): RespuestaComentario {
		$respuesta = $this->respuestas->obtenerPorId( $respuestaId );

		if ( null === $respuesta ) {
			$excepcion = new RespuestaComentarioNoEncontradaException( $respuestaId );

			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje interno construido por la propia excepción, sin entrada de usuario.
			throw $excepcion;
		}

		if ( EstadoRespuestaComentario::PendienteAprobacion !== $respuesta->estado ) {
			$excepcion = new RespuestaComentarioEstadoInvalidoException( $respuestaId );

			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- mensaje interno construido por la propia excepción, sin entrada de usuario.
			throw $excepcion;
		}

		return $respuesta;
	}
}
