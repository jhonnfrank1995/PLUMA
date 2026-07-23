<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Datos\Migrador;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\DetectorEntorno;
use Pluma\Pipeline\EstadoPieza;

/**
 * La página única del panel (Libro de Arquitectura Cap. 10): registra el
 * menú de wp-admin, encola el bundle React y le inyecta los datos iniciales
 * (URL/nonce de REST + textos traducibles + la foto de salud del sistema).
 *
 * Los assets solo se encolan en esta pantalla (pl-wp-core §5): cero peso en
 * cualquier otra pantalla de wp-admin y cero peso en el frontend público.
 * Dentro del bundle, el "shell" de React (barra de estado persistente +
 * enrutado por hash, Cap. 10.1) decide qué pantalla mostrar — Portada,
 * Sala de Máquinas, y las que se añadan en próximas porciones de la Etapa 4.
 */
final class PantallaPanel {

	private const SLUG   = 'pluma-engine-panel';
	private const HANDLE = 'pluma-engine-panel';

	private ?string $hookSuffix = null;

	public function __construct( private readonly DetectorEntorno $detector ) {
	}

	public function registrar(): void {
		add_action( 'admin_menu', array( $this, 'registrarMenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'encolarAssets' ) );
	}

	public function registrarMenu(): void {
		$hook = add_menu_page(
			__( 'PLUMA Engine', 'pluma-engine' ),
			__( 'PLUMA Engine', 'pluma-engine' ),
			Capacidades::CONFIGURAR_MOTOR,
			self::SLUG,
			array( $this, 'renderizar' ),
			'dashicons-edit-large',
			3
		);

		$this->hookSuffix = false !== $hook ? $hook : null;
	}

	public function encolarAssets( string $hookActual ): void {
		if ( null === $this->hookSuffix || $hookActual !== $this->hookSuffix ) {
			return;
		}

		$entrada = $this->leerEntradaManifest();

		if ( null === $entrada ) {
			return;
		}

		[$archivoDir, $archivoUrl] = array( PLUMA_ENGINE_DIR . 'build/panel/', PLUMA_ENGINE_URL . 'build/panel/' );

		$rutaJs = $entrada['file'];
		wp_enqueue_script(
			self::HANDLE,
			$archivoUrl . $rutaJs,
			array(),
			$this->version( $archivoDir . $rutaJs ),
			true
		);
		wp_script_add_data( self::HANDLE, 'type', 'module' );

		foreach ( $entrada['css'] ?? array() as $rutaCss ) {
			wp_enqueue_style(
				self::HANDLE . '-' . md5( $rutaCss ),
				$archivoUrl . $rutaCss,
				array(),
				$this->version( $archivoDir . $rutaCss )
			);
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.plumaPanel = ' . wp_json_encode( $this->datosParaElPanel() ) . ';',
			'before'
		);
	}

	public function renderizar(): void {
		if ( ! current_user_can( Capacidades::CONFIGURAR_MOTOR ) ) {
			return;
		}

		echo '<div id="pluma-panel-root"></div>';
	}

	/**
	 * @return array{file: string, css?: list<string>}|null
	 */
	private function leerEntradaManifest(): ?array {
		$rutaManifest = PLUMA_ENGINE_DIR . 'build/panel/.vite/manifest.json';

		/** @var array<string, array{file: string, css?: list<string>}>|null $manifest */
		$manifest = wp_json_file_decode( $rutaManifest, array( 'associative' => true ) );

		return $manifest['panel/src/main.tsx'] ?? null;
	}

	private function version( string $rutaArchivo ): string {
		$mtime = file_exists( $rutaArchivo ) ? filemtime( $rutaArchivo ) : false;

		return false !== $mtime ? (string) $mtime : PLUMA_ENGINE_VERSION;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function datosParaElPanel(): array {
		return array(
			'restUrl'          => esc_url_raw( rest_url() ),
			'nonce'            => wp_create_nonce( 'wp_rest' ),
			'salud'            => $this->datosSalud(),
			'textosPortada'    => $this->textosPortada(),
			'textosTendencias' => $this->textosTendencias(),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function datosSalud(): array {
		return array(
			'versionPhp'           => $this->detector->versionPhp(),
			'versionWordPress'     => $this->detector->versionWordPress(),
			'versionBaseDatos'     => $this->detector->versionBaseDatos(),
			'versionEsquemaPlugin' => get_option( Migrador::OPCION_VERSION, '0.0.0' ),
			'cronRealConfigurado'  => $this->detector->cronRealConfigurado(),
			'esMultisitio'         => $this->detector->esMultisitio(),
			'textos'               => array(
				'titulo'             => __( 'Sala de Máquinas — Salud del sistema', 'pluma-engine' ),
				'etiquetaPhp'        => __( 'PHP', 'pluma-engine' ),
				'etiquetaWordPress'  => __( 'WordPress', 'pluma-engine' ),
				'etiquetaBaseDatos'  => __( 'Base de datos', 'pluma-engine' ),
				'etiquetaEsquema'    => __( 'Esquema PLUMA', 'pluma-engine' ),
				'etiquetaCron'       => __( 'Cron real', 'pluma-engine' ),
				'cronOk'             => __( 'Configurado', 'pluma-engine' ),
				'cronAdvertencia'    => __( 'WP-Cron activo: no recomendado para producción', 'pluma-engine' ),
				'etiquetaMultisitio' => __( 'Multisitio', 'pluma-engine' ),
				'multisitioSi'       => __( 'Sí', 'pluma-engine' ),
				'multisitioNo'       => __( 'No', 'pluma-engine' ),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function textosPortada(): array {
		return array(
			'titulo'     => __( 'Portada', 'pluma-engine' ),
			'navPortada' => __( 'Portada', 'pluma-engine' ),
			'navSalud'   => __( 'Sala de Máquinas', 'pluma-engine' ),
			'cargando'   => __( 'Cargando…', 'pluma-engine' ),
			'errorCarga' => __( 'No se pudo cargar la Portada. Reintenta en unos segundos.', 'pluma-engine' ),
			'modo'       => array(
				'piloto'   => __( 'Piloto', 'pluma-engine' ),
				'copiloto' => __( 'Copiloto', 'pluma-engine' ),
				'autonomo' => __( 'Autónomo', 'pluma-engine' ),
			),
			'cuota'      => array(
				'titulo'             => __( 'Cuota de hoy', 'pluma-engine' ),
				'publicadas'         => __( 'publicadas', 'pluma-engine' ),
				'programadas'        => __( 'programadas', 'pluma-engine' ),
				'objetivo'           => __( 'objetivo', 'pluma-engine' ),
				'proximaPublicacion' => __( 'Próxima publicación', 'pluma-engine' ),
				'sinProximo'         => __( 'sin ranuras programadas pendientes', 'pluma-engine' ),
				'deficit'            => __( 'Déficit de cuota: por debajo del mínimo configurado', 'pluma-engine' ),
			),
			'salud'      => array(
				'titulo'          => __( 'Salud del motor', 'pluma-engine' ),
				'ultimaEjecucion' => __( 'Última ejecución', 'pluma-engine' ),
				'nunca'           => __( 'el motor no se ha ejecutado todavía', 'pluma-engine' ),
				'gastoHoy'        => __( 'Gasto de hoy', 'pluma-engine' ),
				'deLimite'        => __( 'de', 'pluma-engine' ),
				'errores'         => __( 'con errores en la última ejecución', 'pluma-engine' ),
			),
			'pipeline'   => array(
				'titulo'  => __( 'Piezas en el pipeline', 'pluma-engine' ),
				'estados' => $this->etiquetasEstados(),
			),
			'alertas'    => array(
				'titulo'       => __( 'Alertas', 'pluma-engine' ),
				'retenidas'    => __( 'Retenidas esperando decisión', 'pluma-engine' ),
				'fallidas'     => __( 'Fallidas', 'pluma-engine' ),
				'sinRetenidas' => __( 'ninguna pieza retenida', 'pluma-engine' ),
				'sinFallidas'  => __( 'ninguna pieza fallida', 'pluma-engine' ),
			),
			'tendencias' => array(
				'titulo' => __( 'Tendencias calientes ahora', 'pluma-engine' ),
				'vacio'  => __( 'todavía no se ha detectado ninguna tendencia', 'pluma-engine' ),
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function textosTendencias(): array {
		return array(
			'titulo'          => __( 'Sala de Tendencias', 'pluma-engine' ),
			'cargando'        => __( 'Cargando…', 'pluma-engine' ),
			'errorCarga'      => __( 'No se pudo cargar la Sala de Tendencias. Reintenta en unos segundos.', 'pluma-engine' ),
			'errorAccion'     => __( 'La acción no se pudo completar. Reintenta en unos segundos.', 'pluma-engine' ),
			'vacio'           => __( 'todavía no se ha detectado ninguna tendencia', 'pluma-engine' ),
			'velocidad'       => __( 'Velocidad', 'pluma-engine' ),
			'afinidad'        => __( 'Afinidad', 'pluma-engine' ),
			'total'           => __( 'Puntuación de Oportunidad', 'pluma-engine' ),
			'desgloseParcial' => __( 'Desglose sobre velocidad y afinidad; hueco competitivo y vida útil llegan con el Radar completo.', 'pluma-engine' ),
			'quienCubre'      => __( 'Quién la está cubriendo ya', 'pluma-engine' ),
			'nadieCubre'      => __( 'sin cobertura detectada en las señales', 'pluma-engine' ),
			'estadoVigilada'  => __( 'En vigilancia', 'pluma-engine' ),
			'cubrirAhora'     => __( 'Cubrir ahora', 'pluma-engine' ),
			'ignorar'         => __( 'Ignorar', 'pluma-engine' ),
			'vigilar'         => __( 'Vigilar', 'pluma-engine' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function etiquetasEstados(): array {
		return array(
			EstadoPieza::Detectada->value       => __( 'Detectada', 'pluma-engine' ),
			EstadoPieza::EnInvestigacion->value => __( 'En investigación', 'pluma-engine' ),
			EstadoPieza::Investigada->value     => __( 'Investigada', 'pluma-engine' ),
			EstadoPieza::EnRedaccion->value     => __( 'En redacción', 'pluma-engine' ),
			EstadoPieza::Redactada->value       => __( 'Redactada', 'pluma-engine' ),
			EstadoPieza::Optimizada->value      => __( 'Optimizada', 'pluma-engine' ),
			EstadoPieza::EnRevision->value      => __( 'En revisión', 'pluma-engine' ),
			EstadoPieza::Aprobada->value        => __( 'Aprobada', 'pluma-engine' ),
			EstadoPieza::Programada->value      => __( 'Programada', 'pluma-engine' ),
			EstadoPieza::Publicada->value       => __( 'Publicada', 'pluma-engine' ),
			EstadoPieza::Retenida->value        => __( 'Retenida', 'pluma-engine' ),
			EstadoPieza::Descartada->value      => __( 'Descartada', 'pluma-engine' ),
			EstadoPieza::Fallida->value         => __( 'Fallida', 'pluma-engine' ),
		);
	}
}
