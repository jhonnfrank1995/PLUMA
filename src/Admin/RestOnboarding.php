<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Compuertas\ModoOperacion;
use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Kernel\Activador;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\DetectorEntorno;
use Pluma\Kernel\RelojInterface;
use Pluma\Pipeline\Orquestador;
use Pluma\Taxonomia\TipoVocabulario;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Asistente de instalación de 5 actos (Libro Cap. 10.3): "un sistema
 * funcional en menos de 20 minutos". Protegido con `pluma_configurar_motor`
 * — mismo criterio que la Sala de Máquinas: es configuración técnica del
 * motor, no aprobación editorial.
 *
 * Acto 1 (verificación técnica + cron): reutiliza `DetectorEntorno` (ya
 * usado por la Sala de Máquinas) y expone la URL/token/comando reales del
 * cron — sin detectar el proveedor de hosting (decisión del propietario,
 * 2026-07-23: instrucciones genéricas honestas en vez de arriesgar una
 * receta específica incorrecta para un host no reconocido con certeza).
 * Acto 2 (llaves): ya resuelto por `RestSalaMaquinas`, sin duplicar aquí.
 * Acto 3 (categorías): único importador real de categorías de WordPress
 * hacia `pluma_vocabulario` — nadie más puebla `TipoVocabulario::Categoria`
 * hoy. "Línea editorial" no se persiste aquí — el asistente la recoge en su
 * propio estado y la pasa al crear el periodista (Acto 4).
 * Acto 4 (periodista): ya resuelto por `RestPeriodistas`, sin duplicar aquí.
 * Acto 5 (modo + primer ciclo): único punto que ESCRIBE el modo de
 * operación — hasta ahora solo se leía (Portada, Orquestador). El primer
 * ciclo llama `Orquestador::ejecutarTick()` directamente, autenticado por
 * nonce/capacidad como el resto del panel — el token del motor
 * (`X-Pluma-Token`) es solo para el cron externo real, nunca viaja al JS.
 */
final class RestOnboarding {

	private const RUTA_ESTADO_TECNICO      = '/onboarding/estado-tecnico';
	private const RUTA_IMPORTAR_CATEGORIAS = '/onboarding/importar-categorias';
	private const RUTA_MODO                = '/onboarding/modo';
	private const RUTA_PRIMER_CICLO        = '/onboarding/primer-ciclo';
	private const RUTA_COMPLETAR           = '/onboarding/completar';

	public const OPCION_COMPLETADO = 'pluma_onboarding_completado';

	public function __construct(
		private readonly DetectorEntorno $detector,
		private readonly Orquestador $orquestador,
		private readonly RepositorioVocabularioInterface $vocabulario,
		private readonly RelojInterface $reloj,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_ESTADO_TECNICO,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'estadoTecnico' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_IMPORTAR_CATEGORIAS,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'importarCategorias' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_MODO,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'guardarModo' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_PRIMER_CICLO,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'primerCiclo' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_COMPLETAR,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'completar' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::CONFIGURAR_MOTOR );
	}

	public function estadoTecnico(): WP_REST_Response {
		$token = get_option( Activador::OPCION_MOTOR_TOKEN );

		return new WP_REST_Response(
			array(
				'versionPhp'          => $this->detector->versionPhp(),
				'versionWordPress'    => $this->detector->versionWordPress(),
				'versionBaseDatos'    => $this->detector->versionBaseDatos(),
				'cronRealConfigurado' => $this->detector->cronRealConfigurado(),
				'esMultisitio'        => $this->detector->esMultisitio(),
				'cron'                => array(
					'url'      => esc_url_raw( rest_url( 'pluma/v1/motor/tick' ) ),
					'cabecera' => 'X-Pluma-Token',
					'token'    => is_string( $token ) ? $token : '',
				),
			),
			200
		);
	}

	public function importarCategorias(): WP_REST_Response {
		$existentes = $this->vocabulario->obtenerPorTipo( TipoVocabulario::Categoria );
		$slugsYaHay = array_map( static fn ( $entrada ): string => $entrada->slug, $existentes );
		$importadas = array();
		$yaExistian = array();

		foreach ( get_categories( array( 'hide_empty' => false ) ) as $categoria ) {
			if ( in_array( $categoria->slug, $slugsYaHay, true ) ) {
				$yaExistian[] = $categoria->name;

				continue;
			}

			$this->vocabulario->crear(
				TipoVocabulario::Categoria,
				$categoria->name,
				$categoria->slug,
				array(),
				false,
				$this->reloj->ahora()
			);

			$importadas[] = $categoria->name;
			$slugsYaHay[] = $categoria->slug;
		}

		return new WP_REST_Response(
			array(
				'importadas' => $importadas,
				'yaExistian' => $yaExistian,
			),
			200
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function guardarModo( WP_REST_Request $request ) {
		$modo = $request->get_param( 'modo' );

		if ( ! is_string( $modo ) || null === ModoOperacion::tryFrom( $modo ) ) {
			return new WP_Error( 'pluma_modo_invalido', __( 'Modo de operación desconocido.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		update_option( Orquestador::OPCION_MODO_OPERACION, $modo, false );

		return new WP_REST_Response( array( 'modoOperacion' => $modo ), 200 );
	}

	public function primerCiclo(): WP_REST_Response {
		return new WP_REST_Response( $this->orquestador->ejecutarTick(), 200 );
	}

	public function completar(): WP_REST_Response {
		update_option( self::OPCION_COMPLETADO, true, false );

		return new WP_REST_Response( array( 'completado' => true ), 200 );
	}
}
