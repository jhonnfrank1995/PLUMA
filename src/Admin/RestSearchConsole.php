<?php

declare(strict_types=1);

namespace Pluma\Admin;

use DateTimeImmutable;
use Pluma\Datos\RepositorioMetricasSearchConsoleInterface;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\Cifrado;
use Pluma\Kernel\RelojInterface;
use Pluma\Proveedores\ProveedorSearchConsoleException;
use Pluma\Proveedores\ProveedorSearchConsoleInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Bucle de Search Console (Libro Cap. 6.4): conexión OAuth2 real, selección
 * del sitio verificado y sincronización de métricas (clics/impresiones/CTR/
 * posición por página+consulta). Protegida con `pluma_configurar_motor` —
 * mismo criterio que la Sala de Máquinas: configuración técnica del motor.
 *
 * Cada instalación usa su PROPIO client_id/client_secret de Google Cloud
 * (como la llave de OpenRouter) — un secreto compartido embebido en un
 * plugin distribuido sería una fuga de credenciales entre clientes.
 *
 * Deliberadamente fuera de esta porción: los consumidores del dato real
 * (regenerar títulos débiles, candidatos a pieza de refuerzo, ajuste de
 * asignación por periodista, hueco competitivo). Esta pantalla solo conecta
 * y sincroniza — cero invención de una decisión que todavía no tiene datos
 * reales suficientes detrás.
 */
final class RestSearchConsole {

	private const RUTA_ESTADO       = '/search-console/estado';
	private const RUTA_CREDENCIALES = '/search-console/credenciales';
	private const RUTA_CALLBACK     = '/search-console/callback';
	private const RUTA_SITIOS       = '/search-console/sitios';
	private const RUTA_SITIO        = '/search-console/sitio';
	private const RUTA_SINCRONIZAR  = '/search-console/sincronizar';

	private const OPCION_CLIENT_ID_CIFRADO     = 'pluma_search_console_client_id_cifrado';
	private const OPCION_CLIENT_SECRET_CIFRADO = 'pluma_search_console_client_secret_cifrado';
	private const OPCION_REFRESH_TOKEN_CIFRADO = 'pluma_search_console_refresh_token_cifrado';
	private const OPCION_SITIO                 = 'pluma_search_console_sitio';
	private const OPCION_ULTIMA_SINCRONIZACION = 'pluma_search_console_ultima_sincronizacion';
	private const TRANSIENT_STATE              = 'pluma_search_console_state';
	private const TRANSIENT_ACCESS_TOKEN       = 'pluma_search_console_access_token';
	private const TTL_STATE_SEGUNDOS           = 600;
	private const DIAS_SINCRONIZACION          = 28;
	private const LIMITE_RECIENTES             = 50;

	public function __construct(
		private readonly ProveedorSearchConsoleInterface $proveedor,
		private readonly RepositorioMetricasSearchConsoleInterface $metricas,
		private readonly RelojInterface $reloj,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_ESTADO,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'estado' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_CREDENCIALES,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'guardarCredenciales' ),
					'permission_callback' => array( $this, 'autorizado' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'borrarCredenciales' ),
					'permission_callback' => array( $this, 'autorizado' ),
				),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_CALLBACK,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'callback' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_SITIOS,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'sitios' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_SITIO,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'guardarSitio' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_SINCRONIZAR,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'sincronizar' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::CONFIGURAR_MOTOR );
	}

	public function estado(): WP_REST_Response {
		$sitio                = get_option( self::OPCION_SITIO );
		$ultimaSincronizacion = get_option( self::OPCION_ULTIMA_SINCRONIZACION );

		return new WP_REST_Response(
			array(
				'conectada'            => null !== $this->refreshTokenCifrado(),
				'sitioSeleccionado'    => is_string( $sitio ) && '' !== $sitio ? $sitio : null,
				'circuitoAbierto'      => $this->proveedor->circuitoAbierto(),
				'ultimaSincronizacion' => is_string( $ultimaSincronizacion ) && '' !== $ultimaSincronizacion ? $ultimaSincronizacion : null,
				'metricasRecientes'    => $this->metricas->obtenerRecientes( self::LIMITE_RECIENTES ),
			),
			200
		);
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function guardarCredenciales( WP_REST_Request $request ) {
		$clientId     = $request->get_param( 'clientId' );
		$clientSecret = $request->get_param( 'clientSecret' );

		if ( ! is_string( $clientId ) || '' === trim( $clientId ) || ! is_string( $clientSecret ) || '' === trim( $clientSecret ) ) {
			return new WP_Error( 'pluma_credenciales_invalidas', __( 'client_id y client_secret son obligatorios.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		update_option( self::OPCION_CLIENT_ID_CIFRADO, Cifrado::cifrar( trim( $clientId ) ), false );
		update_option( self::OPCION_CLIENT_SECRET_CIFRADO, Cifrado::cifrar( trim( $clientSecret ) ), false );

		$state = wp_generate_password( 32, false, false );
		set_transient( self::TRANSIENT_STATE, $state, self::TTL_STATE_SEGUNDOS );

		$redirectUri = rest_url( 'pluma/v1' . self::RUTA_CALLBACK );

		return new WP_REST_Response(
			array(
				'redirectUri'     => esc_url_raw( $redirectUri ),
				'urlAutorizacion' => $this->proveedor->urlAutorizacion( trim( $clientId ), $redirectUri, $state ),
			),
			200
		);
	}

	public function borrarCredenciales(): WP_REST_Response {
		delete_option( self::OPCION_CLIENT_ID_CIFRADO );
		delete_option( self::OPCION_CLIENT_SECRET_CIFRADO );
		delete_option( self::OPCION_REFRESH_TOKEN_CIFRADO );
		delete_option( self::OPCION_SITIO );
		delete_option( self::OPCION_ULTIMA_SINCRONIZACION );
		delete_transient( self::TRANSIENT_ACCESS_TOKEN );

		return new WP_REST_Response( array( 'desconectada' => true ), 200 );
	}

	/**
	 * Google redirige aquí el navegador del propio administrador que inició
	 * el flujo — su cookie de sesión de wp-admin viaja con la petición, así
	 * que `permission_callback` (capacidad real, nunca `__return_true`)
	 * autentica correctamente. La verificación de `state` es una segunda
	 * capa anti-CSRF: protege contra un enlace de autorización manipulado
	 * que alguien intente hacer abrir a un administrador ya logueado.
	 *
	 * Devuelve la redirección como cabecera `Location` de un
	 * `WP_REST_Response` (patrón real de la REST API de WordPress:
	 * `WP_REST_Server::serve_request()` envía las cabeceras del response
	 * real al navegador) en vez de `wp_safe_redirect()`+`exit` — así el
	 * método sigue siendo invocable y verificable en un test, sin terminar
	 * el proceso.
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$estadoRecibido = $request->get_param( 'state' );
		$estadoEsperado = get_transient( self::TRANSIENT_STATE );
		delete_transient( self::TRANSIENT_STATE );

		// El fragmento `#/salud` va al final, DESPUÉS de `add_query_arg()`:
		// un `#` en la URL base haría que el query arg se anexara dentro del
		// fragmento (invisible para PHP en la siguiente carga, pero además
		// semánticamente incorrecto) en vez de la cadena de consulta real.
		$base = admin_url( 'admin.php?page=pluma-engine-panel' );

		if ( ! is_string( $estadoRecibido ) || ! is_string( $estadoEsperado ) || ! hash_equals( $estadoEsperado, $estadoRecibido ) ) {
			return $this->redireccion( add_query_arg( 'search_console', 'estado_invalido', $base ) . '#/salud' );
		}

		$code         = $request->get_param( 'code' );
		$clientId     = $this->clientIdDescifrado();
		$clientSecret = $this->clientSecretDescifrado();

		if ( ! is_string( $code ) || '' === $code || null === $clientId || null === $clientSecret ) {
			return $this->redireccion( add_query_arg( 'search_console', 'error', $base ) . '#/salud' );
		}

		try {
			$tokens = $this->proveedor->intercambiarCodigo( $clientId, $clientSecret, $code, rest_url( 'pluma/v1' . self::RUTA_CALLBACK ) );
		} catch ( ProveedorSearchConsoleException $excepcion ) {
			return $this->redireccion( add_query_arg( 'search_console', 'error', $base ) . '#/salud' );
		}

		if ( null === $tokens->refreshToken ) {
			return $this->redireccion( add_query_arg( 'search_console', 'sin_refresh_token', $base ) . '#/salud' );
		}

		update_option( self::OPCION_REFRESH_TOKEN_CIFRADO, Cifrado::cifrar( $tokens->refreshToken ), false );
		$this->cachearAccessToken( $tokens->accessToken, $tokens->expiraEnSegundos );

		return $this->redireccion( add_query_arg( 'search_console', 'conectado', $base ) . '#/salud' );
	}

	private function redireccion( string $url ): WP_REST_Response {
		$respuesta = new WP_REST_Response( null, 302 );
		$respuesta->header( 'Location', $url );

		return $respuesta;
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function sitios() {
		$accessToken = $this->accessTokenValido();

		if ( null === $accessToken ) {
			return $this->errorNoConectado();
		}

		try {
			return new WP_REST_Response( $this->proveedor->listarSitios( $accessToken ), 200 );
		} catch ( ProveedorSearchConsoleException $excepcion ) {
			return $this->errorProveedor( $excepcion );
		}
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function guardarSitio( WP_REST_Request $request ) {
		$siteUrl = $request->get_param( 'siteUrl' );

		if ( ! is_string( $siteUrl ) || '' === trim( $siteUrl ) ) {
			return new WP_Error( 'pluma_sitio_invalido', __( 'El sitio no puede estar vacío.', 'pluma-engine' ), array( 'status' => 400 ) );
		}

		update_option( self::OPCION_SITIO, trim( $siteUrl ), false );

		return new WP_REST_Response( array( 'sitioSeleccionado' => trim( $siteUrl ) ), 200 );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function sincronizar() {
		$accessToken = $this->accessTokenValido();
		$sitio       = get_option( self::OPCION_SITIO );

		if ( null === $accessToken || ! is_string( $sitio ) || '' === $sitio ) {
			return $this->errorNoConectado();
		}

		$ahora = $this->reloj->ahora();
		$desde = $ahora->modify( '-' . self::DIAS_SINCRONIZACION . ' days' );

		try {
			$filas = $this->proveedor->consultarAnalitica( $accessToken, $sitio, $desde, $ahora );
		} catch ( ProveedorSearchConsoleException $excepcion ) {
			return $this->errorProveedor( $excepcion );
		}

		$guardadas = $this->metricas->guardarLote( $filas, $ahora );
		update_option( self::OPCION_ULTIMA_SINCRONIZACION, $ahora->format( DATE_ATOM ), false );

		return new WP_REST_Response(
			array(
				'filasRecibidas' => count( $filas ),
				'filasGuardadas' => $guardadas,
			),
			200
		);
	}

	private function accessTokenValido(): ?string {
		$cacheado = get_transient( self::TRANSIENT_ACCESS_TOKEN );

		if ( is_string( $cacheado ) && '' !== $cacheado ) {
			return $cacheado;
		}

		$refreshToken = $this->refreshTokenCifrado();
		$clientId     = $this->clientIdDescifrado();
		$clientSecret = $this->clientSecretDescifrado();

		if ( null === $refreshToken || null === $clientId || null === $clientSecret ) {
			return null;
		}

		try {
			$tokens = $this->proveedor->refrescarAccessToken( $clientId, $clientSecret, $refreshToken );
		} catch ( ProveedorSearchConsoleException $excepcion ) {
			return null;
		}

		$this->cachearAccessToken( $tokens->accessToken, $tokens->expiraEnSegundos );

		return $tokens->accessToken;
	}

	private function cachearAccessToken( string $accessToken, int $expiraEnSegundos ): void {
		set_transient( self::TRANSIENT_ACCESS_TOKEN, $accessToken, max( 60, $expiraEnSegundos - 60 ) );
	}

	private function refreshTokenCifrado(): ?string {
		$sobre = get_option( self::OPCION_REFRESH_TOKEN_CIFRADO );

		return is_string( $sobre ) && '' !== $sobre ? Cifrado::descifrar( $sobre ) : null;
	}

	private function clientIdDescifrado(): ?string {
		$sobre = get_option( self::OPCION_CLIENT_ID_CIFRADO );

		return is_string( $sobre ) && '' !== $sobre ? Cifrado::descifrar( $sobre ) : null;
	}

	private function clientSecretDescifrado(): ?string {
		$sobre = get_option( self::OPCION_CLIENT_SECRET_CIFRADO );

		return is_string( $sobre ) && '' !== $sobre ? Cifrado::descifrar( $sobre ) : null;
	}

	private function errorNoConectado(): WP_Error {
		return new WP_Error( 'pluma_search_console_no_conectado', __( 'Search Console no está conectado todavía.', 'pluma-engine' ), array( 'status' => 409 ) );
	}

	private function errorProveedor( ProveedorSearchConsoleException $excepcion ): WP_Error {
		return new WP_Error( 'pluma_search_console_error', $excepcion->getMessage(), array( 'status' => 502 ) );
	}
}
