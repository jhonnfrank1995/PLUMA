<?php

declare(strict_types=1);

namespace Pluma\Tests\Integration;

use Pluma\Datos\RepositorioPiezas;
use Pluma\Datos\RepositorioRespuestasComentarios;
use Pluma\Datos\RepositorioTendencias;
use Pluma\Kernel\RelojSistema;
use Pluma\Redaccion\EstadoRespuestaComentario;
use Pluma\Sensores\PuntuacionOportunidad;
use Pluma\Sensores\TendenciaDetectada;
use WP_UnitTestCase;

/**
 * `pluma_respuestas_comentarios` contra tablas reales (Libro Cap. 5.7,
 * Etapa 5: memoria de audiencia + respuestas asistidas).
 *
 * @covers \Pluma\Datos\RepositorioRespuestasComentarios
 */
final class RepositorioRespuestasComentariosTest extends WP_UnitTestCase {

	private function crearPieza( RepositorioTendencias $repoTendencias, RepositorioPiezas $repoPiezas, RelojSistema $reloj ): int {
		$tendenciaId = $repoTendencias->guardar(
			new TendenciaDetectada( 'tendencia respuesta ' . uniqid(), PuntuacionOportunidad::calcular( 50, 50 ), $reloj->ahora(), array(), 'google_trends' ),
			$reloj->ahora()
		);

		return $repoPiezas->crear( $tendenciaId, $reloj->ahora() );
	}

	public function test_registrar_y_obtener_por_id(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();

		$piezaId = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );

		$id = $repo->registrar( $piezaId, 555111, 7, 'un borrador de respuesta', EstadoRespuestaComentario::PendienteAprobacion, $reloj->ahora() );

		self::assertGreaterThan( 0, $id );

		$respuesta = $repo->obtenerPorId( $id );
		self::assertNotNull( $respuesta );
		self::assertSame( $piezaId, $respuesta->piezaId );
		self::assertSame( 555111, $respuesta->comentarioId );
		self::assertSame( 7, $respuesta->periodistaId );
		self::assertSame( 'un borrador de respuesta', $respuesta->borrador );
		self::assertSame( EstadoRespuestaComentario::PendienteAprobacion, $respuesta->estado );
		self::assertNull( $respuesta->comentarioRespuestaId );
		self::assertNull( $respuesta->resueltaEn );
	}

	public function test_ya_procesado_es_verdadero_solo_tras_registrar_ese_comentario(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();

		$piezaId      = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );
		$comentarioId = random_int( 1000000, 9999999 );

		self::assertFalse( $repo->yaProcesado( $comentarioId ) );

		$repo->registrar( $piezaId, $comentarioId, null, null, EstadoRespuestaComentario::Procesado, $reloj->ahora() );

		self::assertTrue( $repo->yaProcesado( $comentarioId ) );
	}

	public function test_obtener_pendientes_solo_devuelve_las_que_esperan_aprobacion(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();

		$piezaId = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );

		$pendienteId = $repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $reloj->ahora() );
		$repo->registrar( $piezaId, random_int( 1000000, 9999999 ), null, null, EstadoRespuestaComentario::Procesado, $reloj->ahora() );

		$idsPendientes = array_map( static fn ( $r ) => $r->id, $repo->obtenerPendientes( 100 ) );

		self::assertContains( $pendienteId, $idsPendientes );
		self::assertSame( count( $idsPendientes ), count( array_unique( $idsPendientes ) ) );
	}

	public function test_contar_pendientes_refleja_solo_las_pendientes(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();

		$piezaId    = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );
		$totalAntes = $repo->contarPendientes();

		$repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $reloj->ahora() );

		self::assertSame( $totalAntes + 1, $repo->contarPendientes() );
	}

	public function test_marcar_resuelta_actualiza_estado_y_comentario_respuesta(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();

		$piezaId = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );
		$id      = $repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $reloj->ahora() );

		self::assertTrue( $repo->marcarResuelta( $id, EstadoRespuestaComentario::Aprobado, 987654, $reloj->ahora() ) );

		$respuesta = $repo->obtenerPorId( $id );
		self::assertNotNull( $respuesta );
		self::assertSame( EstadoRespuestaComentario::Aprobado, $respuesta->estado );
		self::assertSame( 987654, $respuesta->comentarioRespuestaId );
		self::assertNotNull( $respuesta->resueltaEn );
	}

	public function test_contar_creados_entre_filtra_por_rango_de_fechas(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();
		$ahora          = $reloj->ahora();

		$piezaId    = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );
		$totalAntes = $repo->contarCreadosEntre( $ahora->modify( '-7 days' ), $ahora );

		$repo->registrar( $piezaId, random_int( 1000000, 9999999 ), null, null, EstadoRespuestaComentario::Procesado, $ahora );
		$repo->registrar( $piezaId, random_int( 1000000, 9999999 ), null, null, EstadoRespuestaComentario::Procesado, $ahora->modify( '-10 days' ) );

		self::assertSame( $totalAntes + 1, $repo->contarCreadosEntre( $ahora->modify( '-7 days' ), $ahora ) );
	}

	public function test_contar_por_estado_resuelto_entre_filtra_por_rango_de_fechas(): void {
		global $wpdb;
		$repoPiezas     = new RepositorioPiezas( $wpdb );
		$repoTendencias = new RepositorioTendencias( $wpdb );
		$repo           = new RepositorioRespuestasComentarios( $wpdb );
		$reloj          = new RelojSistema();
		$ahora          = $reloj->ahora();

		$piezaId = $this->crearPieza( $repoTendencias, $repoPiezas, $reloj );

		$aprobadaDentroId = $repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $ahora );
		$repo->marcarResuelta( $aprobadaDentroId, EstadoRespuestaComentario::Aprobado, 111, $ahora );

		$aprobadaFueraId = $repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $ahora->modify( '-10 days' ) );
		$repo->marcarResuelta( $aprobadaFueraId, EstadoRespuestaComentario::Aprobado, 222, $ahora->modify( '-10 days' ) );

		$descartadaDentroId = $repo->registrar( $piezaId, random_int( 1000000, 9999999 ), 7, 'borrador', EstadoRespuestaComentario::PendienteAprobacion, $ahora );
		$repo->marcarResuelta( $descartadaDentroId, EstadoRespuestaComentario::Descartado, null, $ahora );

		$desde = $ahora->modify( '-7 days' );

		self::assertSame( 1, $repo->contarPorEstadoResueltoEntre( EstadoRespuestaComentario::Aprobado, $desde, $ahora ) );
		self::assertSame( 1, $repo->contarPorEstadoResueltoEntre( EstadoRespuestaComentario::Descartado, $desde, $ahora ) );
	}
}
