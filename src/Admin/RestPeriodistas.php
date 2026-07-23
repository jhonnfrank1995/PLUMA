<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Datos\RepositorioPeriodistasInterface;
use Pluma\Datos\RepositorioMemoriaEditorialInterface;
use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\RelojInterface;
use Pluma\Redaccion\Diales;
use Pluma\Redaccion\EntradaMemoria;
use Pluma\Redaccion\GeneradorVistaPrevia;
use Pluma\Redaccion\MatrizTonos;
use Pluma\Redaccion\Periodista;
use Pluma\Redaccion\PlantillaPeriodista;
use Pluma\Redaccion\PlantillasSiembra;
use Pluma\Redaccion\ReglasConducta;
use Pluma\Proveedores\ProveedorLenguajeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Banco de Periodistas + Estudio de Conducta (Libro Cap. 10.2, "la pantalla
 * estrella"): tarjetas con métricas vivas, crear desde plantilla, clonar,
 * ajustar la Conducta (nueva versión, nunca sobrescribe), jubilar, y la
 * vista previa en vivo. Protegido con `pluma_gestionar_periodistas` — la
 * misma capacidad que ya protege export/import del banco.
 */
final class RestPeriodistas {

	private const RUTA_LISTAR                = '/periodistas';
	private const RUTA_PLANTILLAS            = '/periodistas/plantillas';
	private const RUTA_CREAR_DESDE_PLANTILLA = '/periodistas/plantilla';
	private const RUTA_VISTA_PREVIA          = '/periodistas/vista-previa';
	private const RUTA_DETALLE               = '/periodistas/(?P<id>\d+)';
	private const RUTA_CLONAR                = '/periodistas/(?P<id>\d+)/clonar';
	private const RUTA_CONDUCTA              = '/periodistas/(?P<id>\d+)/conducta';
	private const RUTA_JUBILAR               = '/periodistas/(?P<id>\d+)/jubilar';

	private const LIMITE_MEMORIA_RECIENTE = 20;

	/**
	 * @var array<string, callable(): PlantillaPeriodista>
	 */
	private const PLANTILLAS = array(
		'analista'   => array( PlantillasSiembra::class, 'analistaDeDatosSobrio' ),
		'columnista' => array( PlantillasSiembra::class, 'columnistaCriticaVehemente' ),
		'cronista'   => array( PlantillasSiembra::class, 'cronistaSatirico' ),
	);

	public function __construct(
		private readonly RepositorioPeriodistasInterface $periodistas,
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioMemoriaEditorialInterface $memoria,
		private readonly GeneradorVistaPrevia $generadorVistaPrevia,
		private readonly RelojInterface $reloj,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_PLANTILLAS,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'plantillas' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_LISTAR,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'listar' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_CREAR_DESDE_PLANTILLA,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'crearDesdePlantilla' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_VISTA_PREVIA,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'vistaPrevia' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_DETALLE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'detalle' ),
				'permission_callback' => array( $this, 'autorizado' ),
				'args'                => $this->argumentoId(),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_CLONAR,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clonar' ),
				'permission_callback' => array( $this, 'autorizado' ),
				'args'                => $this->argumentoId(),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_CONDUCTA,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ajustarConducta' ),
				'permission_callback' => array( $this, 'autorizado' ),
				'args'                => $this->argumentoId(),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_JUBILAR,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'jubilar' ),
				'permission_callback' => array( $this, 'autorizado' ),
				'args'                => $this->argumentoId(),
			)
		);
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::GESTIONAR_PERIODISTAS );
	}

	public function plantillas(): WP_REST_Response {
		$respuesta = array();

		foreach ( self::PLANTILLAS as $slug => $fabrica ) {
			$plantilla   = call_user_func( $fabrica );
			$respuesta[] = array(
				'slug'      => $slug,
				'nombre'    => $plantilla->nombre,
				'biografia' => $plantilla->biografia,
				'rol'       => $plantilla->rol->value,
			);
		}

		return new WP_REST_Response( $respuesta, 200 );
	}

	public function listar(): WP_REST_Response {
		$respuesta = array_map(
			fn ( Periodista $periodista ): array => $this->resumenTarjeta( $periodista ),
			$this->periodistas->obtenerTodos()
		);

		return new WP_REST_Response( $respuesta, 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function detalle( WP_REST_Request $request ) {
		$periodista = $this->periodistas->obtenerPorId( (int) $request->get_param( 'id' ) );

		if ( null === $periodista ) {
			return $this->errorNoEncontrado();
		}

		$memoriaReciente = array_map(
			static fn ( EntradaMemoria $entrada ): array => array(
				'tipo'      => $entrada->tipo->value,
				'tema'      => $entrada->tema,
				'contenido' => $entrada->contenido,
				'creadaEn'  => $entrada->creadaEn->format( DATE_ATOM ),
			),
			$this->memoria->obtenerPorPeriodista( $periodista->id, null, self::LIMITE_MEMORIA_RECIENTE )
		);

		return new WP_REST_Response(
			array(
				'id'              => $periodista->id,
				'nombre'          => $periodista->nombre,
				'avatarUrl'       => $periodista->avatarUrl,
				'biografia'       => $periodista->biografia,
				'rol'             => $periodista->rol->value,
				'especialidades'  => array_map( static fn ( $e ): array => $e->aArray(), $periodista->especialidades ),
				'estado'          => $periodista->estado->value,
				'diales'          => $periodista->conductaActual->diales->aArray(),
				'reglasConducta'  => $periodista->conductaActual->reglas->aArray(),
				'matrizTonos'     => $periodista->conductaActual->matrizTonos->aArray(),
				'metricas'        => $this->piezas->metricasPorPeriodista( $periodista->id ),
				'memoriaReciente' => $memoriaReciente,
			),
			200
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function crearDesdePlantilla( WP_REST_Request $request ) {
		$slug = $request->get_param( 'plantilla' );

		if ( ! is_string( $slug ) || ! isset( self::PLANTILLAS[ $slug ] ) ) {
			return new WP_Error( 'pluma_plantilla_invalida', __( 'Plantilla desconocida.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		$plantilla      = call_user_func( self::PLANTILLAS[ $slug ] );
		$nombre         = $request->get_param( 'nombre' );
		$lineaEditorial = $request->get_param( 'lineaEditorial' );
		$reglas         = is_string( $lineaEditorial ) && '' !== trim( $lineaEditorial )
			? new ReglasConducta(
				sanitize_textarea_field( $lineaEditorial ),
				$plantilla->reglas->lineasRojas,
				$plantilla->reglas->muletillas,
				$plantilla->reglas->vocabularioProhibido,
				$plantilla->reglas->tratamientoLector,
				$plantilla->reglas->estiloPreguntaFinal
			)
			: $plantilla->reglas;

		$id = $this->periodistas->crear(
			is_string( $nombre ) && '' !== trim( $nombre ) ? sanitize_text_field( $nombre ) : $plantilla->nombre,
			$plantilla->avatarUrl,
			$plantilla->biografia,
			$plantilla->rol,
			$plantilla->especialidades,
			$plantilla->estado,
			$plantilla->diales,
			$reglas,
			$plantilla->matrizTonos,
			$this->reloj->ahora()
		);

		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function clonar( WP_REST_Request $request ) {
		$origen = $this->periodistas->obtenerPorId( (int) $request->get_param( 'id' ) );

		if ( null === $origen ) {
			return $this->errorNoEncontrado();
		}

		$nombreNuevo = $request->get_param( 'nombre' );

		if ( ! is_string( $nombreNuevo ) || '' === trim( $nombreNuevo ) ) {
			return new WP_Error( 'pluma_nombre_requerido', __( 'El clon necesita un nombre nuevo.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		$id = $this->periodistas->crear(
			sanitize_text_field( $nombreNuevo ),
			$origen->avatarUrl,
			$origen->biografia,
			$origen->rol,
			$origen->especialidades,
			$origen->estado,
			$origen->conductaActual->diales,
			$origen->conductaActual->reglas,
			$origen->conductaActual->matrizTonos,
			$this->reloj->ahora()
		);

		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function ajustarConducta( WP_REST_Request $request ) {
		$periodistaId = (int) $request->get_param( 'id' );

		if ( null === $this->periodistas->obtenerPorId( $periodistaId ) ) {
			return $this->errorNoEncontrado();
		}

		$conducta = $this->conductaCandidataDesdeRequest( $request );

		if ( null === $conducta ) {
			return $this->errorConductaInvalida();
		}

		[$diales, $reglas, $matriz] = $conducta;

		$versionId = $this->periodistas->nuevaVersionConducta( $periodistaId, $diales, $reglas, $matriz, $this->reloj->ahora() );

		return new WP_REST_Response( array( 'versionId' => $versionId ), 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function jubilar( WP_REST_Request $request ) {
		$periodistaId = (int) $request->get_param( 'id' );

		if ( null === $this->periodistas->obtenerPorId( $periodistaId ) ) {
			return $this->errorNoEncontrado();
		}

		$this->periodistas->jubilar( $periodistaId, $this->reloj->ahora() );

		return new WP_REST_Response( array( 'id' => $periodistaId ), 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function vistaPrevia( WP_REST_Request $request ) {
		$periodistaIdParam = $request->get_param( 'periodistaId' );

		if ( ! is_numeric( $periodistaIdParam ) ) {
			return new WP_Error( 'pluma_periodista_invalido', __( 'Falta el periodista.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		$periodista = $this->periodistas->obtenerPorId( (int) $periodistaIdParam );

		if ( null === $periodista ) {
			return $this->errorNoEncontrado();
		}

		$conducta = $this->conductaCandidataDesdeRequest( $request );

		if ( null === $conducta ) {
			return $this->errorConductaInvalida();
		}

		[$diales, $reglas, $matriz] = $conducta;

		try {
			$texto = $this->generadorVistaPrevia->generar( $periodista, $diales, $reglas, $matriz );
		} catch ( ProveedorLenguajeException $e ) {
			$estado = $e->presupuestoAgotado ? 409 : 503;

			return new WP_Error( 'pluma_vista_previa_no_disponible', $e->getMessage(), array( 'status' => $estado ) );
		}

		return new WP_REST_Response( array( 'texto' => $texto ), 200 );
	}

	/**
	 * @return array{0: Diales, 1: ReglasConducta, 2: MatrizTonos}|null
	 */
	private function conductaCandidataDesdeRequest( WP_REST_Request $request ): ?array {
		$diales      = $request->get_param( 'diales' );
		$reglas      = $request->get_param( 'reglasConducta' );
		$matrizTonos = $request->get_param( 'matrizTonos' );

		if ( ! is_array( $diales ) || ! is_array( $reglas ) || ! is_array( $matrizTonos ) ) {
			return null;
		}

		try {
			/** @var array{agudezaCritica: int, humor: int, satira: int, formalidad: int, vehemencia: int, empatia: int, densidadDatos: int, longitudPreferida: int} $diales */
			/** @var array{lineaEditorial: string, lineasRojas: list<string>, muletillas: list<string>, vocabularioProhibido: list<string>, tratamientoLector: string, estiloPreguntaFinal: string} $reglas */
			/** @var array<string, array{tipoNoticia: string, tonoDominante: string, tonoApoyo: string, nivelSatira: string}> $matrizTonos */
			return array( Diales::desdeArray( $diales ), ReglasConducta::desdeArray( $reglas ), MatrizTonos::desdeArray( $matrizTonos ) );
		} catch ( \Throwable ) {
			return null;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resumenTarjeta( Periodista $periodista ): array {
		return array(
			'id'             => $periodista->id,
			'nombre'         => $periodista->nombre,
			'avatarUrl'      => $periodista->avatarUrl,
			'rol'            => $periodista->rol->value,
			'especialidades' => array_map( static fn ( $e ): array => $e->aArray(), $periodista->especialidades ),
			'estado'         => $periodista->estado->value,
			'metricas'       => $this->piezas->metricasPorPeriodista( $periodista->id ),
		);
	}

	private function errorNoEncontrado(): WP_Error {
		return new WP_Error( 'pluma_periodista_no_encontrado', __( 'Periodista no encontrado.', 'pluma-engine' ), array( 'status' => 404 ) );
	}

	private function errorConductaInvalida(): WP_Error {
		return new WP_Error( 'pluma_conducta_invalida', __( 'Faltan o son inválidos los diales, reglas o matriz de tonos.', 'pluma-engine' ), array( 'status' => 400 ) );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function argumentoId(): array {
		return array(
			'id' => array(
				'required'          => true,
				'validate_callback' => static fn ( $valor ): bool => is_numeric( $valor ),
			),
		);
	}
}
