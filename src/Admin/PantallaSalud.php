<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Datos\Migrador;
use Pluma\Kernel\Capacidades;
use Pluma\Kernel\DetectorEntorno;

/**
 * Sala de Máquinas — Salud del sistema.
 *
 * Primera pantalla del panel (Libro de Arquitectura Cap. 10): estado técnico
 * del hosting en segundos. Los assets solo se encolan en esta pantalla
 * (pl-wp-core §5): cero peso en cualquier otra pantalla de wp-admin y cero
 * peso en el frontend público.
 */
final class PantallaSalud {

	private const SLUG   = 'pluma-engine-salud';
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
			'window.plumaSalud = ' . wp_json_encode( $this->datosParaElPanel() ) . ';',
			'before'
		);
	}

	public function renderizar(): void {
		if ( ! current_user_can( Capacidades::CONFIGURAR_MOTOR ) ) {
			return;
		}

		echo '<div id="pluma-salud-root"></div>';
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
}
