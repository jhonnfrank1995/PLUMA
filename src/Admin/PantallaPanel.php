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
			'restUrl'                => esc_url_raw( rest_url() ),
			'nonce'                  => wp_create_nonce( 'wp_rest' ),
			'salud'                  => $this->datosSalud(),
			'textosPortada'          => $this->textosPortada(),
			'textosTendencias'       => $this->textosTendencias(),
			'textosMesaEditorial'    => $this->textosMesaEditorial(),
			'textosBancoPeriodistas' => $this->textosBancoPeriodistas(),
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
	private function textosMesaEditorial(): array {
		return array(
			'titulo'               => __( 'Mesa Editorial', 'pluma-engine' ),
			'cargando'             => __( 'Cargando…', 'pluma-engine' ),
			'errorCarga'           => __( 'No se pudo cargar la Mesa Editorial. Reintenta en unos segundos.', 'pluma-engine' ),
			'errorAccion'          => __( 'La acción no se pudo completar. Reintenta en unos segundos.', 'pluma-engine' ),
			'columnaVacia'         => __( 'sin piezas en este estado', 'pluma-engine' ),
			'sinPeriodista'        => __( 'sin periodista asignado', 'pluma-engine' ),
			'sinTesis'             => __( 'sin tesis todavía', 'pluma-engine' ),
			'cerrar'               => __( 'Cerrar', 'pluma-engine' ),
			'expediente'           => __( 'Expediente', 'pluma-engine' ),
			'sinExpediente'        => __( 'sin expediente todavía', 'pluma-engine' ),
			'nivelVerificado'      => __( 'Verificado', 'pluma-engine' ),
			'nivelAtribuido'       => __( 'Atribuido', 'pluma-engine' ),
			'nivelDisputado'       => __( 'Disputado', 'pluma-engine' ),
			'ficha'                => __( 'Ficha de Decisión Editorial', 'pluma-engine' ),
			'sinFicha'             => __( 'sin ficha de decisión editorial todavía', 'pluma-engine' ),
			'tesisElegida'         => __( 'Tesis elegida', 'pluma-engine' ),
			'tonoDominante'        => __( 'Tono dominante', 'pluma-engine' ),
			'tonoApoyo'            => __( 'Tono de apoyo', 'pluma-engine' ),
			'compuertas'           => __( 'Compuertas', 'pluma-engine' ),
			'sinCompuertas'        => __( 'sin evaluación de compuertas todavía', 'pluma-engine' ),
			'calidad'              => __( 'Calidad', 'pluma-engine' ),
			'riesgo'               => __( 'Riesgo', 'pluma-engine' ),
			'originalidad'         => __( 'Originalidad', 'pluma-engine' ),
			'motivos'              => __( 'Motivos', 'pluma-engine' ),
			'borradores'           => __( 'Borradores', 'pluma-engine' ),
			'sinBorradores'        => __( 'sin borradores todavía', 'pluma-engine' ),
			'cicloAnterior'        => __( 'Ciclo anterior', 'pluma-engine' ),
			'cicloActual'          => __( 'Ciclo', 'pluma-engine' ),
			'editadoManualmente'   => __( 'editado manualmente por un editor', 'pluma-engine' ),
			'aprobadoPorCorrector' => __( 'aprobado por el Corrector Interno', 'pluma-engine' ),
			'editar'               => __( 'Editar', 'pluma-engine' ),
			'guardarEdicion'       => __( 'Guardar edición', 'pluma-engine' ),
			'cancelar'             => __( 'Cancelar', 'pluma-engine' ),
			'contenidoVacio'       => __( 'El contenido no puede estar vacío.', 'pluma-engine' ),
			'reasignar'            => __( 'Periodista asignado', 'pluma-engine' ),
			'reasignarBoton'       => __( 'Reasignar', 'pluma-engine' ),
			'aprobar'              => __( 'Forzar aprobación', 'pluma-engine' ),
			'descartar'            => __( 'Descartar', 'pluma-engine' ),
			'confirmarDescartar'   => __( '¿Descartar esta Pieza? Esta acción queda registrada en la auditoría y no se puede deshacer.', 'pluma-engine' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function textosBancoPeriodistas(): array {
		return array(
			'titulo'              => __( 'Banco de Periodistas', 'pluma-engine' ),
			'cargando'            => __( 'Cargando…', 'pluma-engine' ),
			'errorCarga'          => __( 'No se pudo cargar el Banco de Periodistas. Reintenta en unos segundos.', 'pluma-engine' ),
			'errorAccion'         => __( 'La acción no se pudo completar. Reintenta en unos segundos.', 'pluma-engine' ),
			'sinPeriodistas'      => __( 'todavía no hay ningún periodista en el banco', 'pluma-engine' ),
			'piezasPublicadas'    => __( 'piezas publicadas', 'pluma-engine' ),
			'verticalesTop'       => __( 'Verticales donde más publica', 'pluma-engine' ),
			'sinVerticales'       => __( 'sin piezas publicadas todavía', 'pluma-engine' ),
			'estadoActivo'        => __( 'Activo', 'pluma-engine' ),
			'estadoJubilado'      => __( 'Jubilado', 'pluma-engine' ),
			'crearDesdePlantilla' => __( 'Crear desde plantilla', 'pluma-engine' ),
			'elegirPlantilla'     => __( 'Elegir plantilla', 'pluma-engine' ),
			'nombreOpcional'      => __( 'Nombre (opcional, por defecto el de la plantilla)', 'pluma-engine' ),
			'crear'               => __( 'Crear', 'pluma-engine' ),
			'cancelar'            => __( 'Cancelar', 'pluma-engine' ),
			'jubilar'             => __( 'Jubilar', 'pluma-engine' ),
			'confirmarJubilar'    => __( '¿Jubilar a este periodista? Sus piezas quedan, pero deja de recibir asignaciones nuevas.', 'pluma-engine' ),
			'cerrar'              => __( 'Cerrar', 'pluma-engine' ),
			'estudioDeConducta'   => __( 'Estudio de Conducta', 'pluma-engine' ),
			'identidad'           => __( 'Identidad', 'pluma-engine' ),
			'diales'              => array(
				'titulo'            => __( 'Diales de temperamento', 'pluma-engine' ),
				'agudezaCritica'    => __( 'Agudeza crítica', 'pluma-engine' ),
				'humor'             => __( 'Humor', 'pluma-engine' ),
				'satira'            => __( 'Sátira', 'pluma-engine' ),
				'formalidad'        => __( 'Formalidad', 'pluma-engine' ),
				'vehemencia'        => __( 'Vehemencia', 'pluma-engine' ),
				'empatia'           => __( 'Empatía', 'pluma-engine' ),
				'densidadDatos'     => __( 'Densidad de datos', 'pluma-engine' ),
				'longitudPreferida' => __( 'Longitud preferida', 'pluma-engine' ),
			),
			'reglas'              => array(
				'titulo'               => __( 'Reglas de conducta', 'pluma-engine' ),
				'lineaEditorial'       => __( 'Línea editorial', 'pluma-engine' ),
				'lineasRojas'          => __( 'Líneas rojas', 'pluma-engine' ),
				'muletillas'           => __( 'Muletillas / rasgos de voz', 'pluma-engine' ),
				'vocabularioProhibido' => __( 'Vocabulario prohibido', 'pluma-engine' ),
				'tratamientoLector'    => __( 'Trato al lector', 'pluma-engine' ),
				'tratamientoTu'        => __( 'De tú', 'pluma-engine' ),
				'tratamientoUsted'     => __( 'De usted', 'pluma-engine' ),
				'estiloPreguntaFinal'  => __( 'Estilo de pregunta final', 'pluma-engine' ),
				'agregar'              => __( 'Agregar', 'pluma-engine' ),
			),
			'matriz'              => array(
				'titulo'        => __( 'Matriz de tonos', 'pluma-engine' ),
				'tipoNoticia'   => array(
					'anuncio_corporativo' => __( 'Anuncio corporativo', 'pluma-engine' ),
					'escandalo_politico'  => __( 'Escándalo político', 'pluma-engine' ),
					'tragedia'            => __( 'Tragedia', 'pluma-engine' ),
					'cultura_viral'       => __( 'Cultura viral', 'pluma-engine' ),
					'dato_economico'      => __( 'Dato económico', 'pluma-engine' ),
				),
				'tonoDominante' => __( 'Tono dominante', 'pluma-engine' ),
				'tonoApoyo'     => __( 'Tono de apoyo', 'pluma-engine' ),
				'nivelSatira'   => __( 'Sátira permitida', 'pluma-engine' ),
				'tono'          => array(
					'analitico'            => __( 'Analítico', 'pluma-engine' ),
					'critico'              => __( 'Crítico', 'pluma-engine' ),
					'informativo_empatico' => __( 'Informativo empático', 'pluma-engine' ),
					'humoristico'          => __( 'Humorístico', 'pluma-engine' ),
					'opinion'              => __( 'Opinión', 'pluma-engine' ),
					'persuasivo'           => __( 'Persuasivo', 'pluma-engine' ),
				),
				'satira'        => array(
					'bloqueada'      => __( 'Bloqueada', 'pluma-engine' ),
					'no'             => __( 'No', 'pluma-engine' ),
					'con_moderacion' => __( 'Con moderación', 'pluma-engine' ),
					'en_remate'      => __( 'Solo en el remate', 'pluma-engine' ),
					'pieza_completa' => __( 'Pieza completa', 'pluma-engine' ),
				),
				'filaSistema'   => __( 'Informativo empático / Analítico / Sátira bloqueada — regla de sistema, no editable.', 'pluma-engine' ),
			),
			'memoria'             => array(
				'titulo' => __( 'Memoria editorial reciente', 'pluma-engine' ),
				'vacia'  => __( 'sin memoria registrada todavía', 'pluma-engine' ),
				'tipo'   => array(
					'postura'   => __( 'Postura', 'pluma-engine' ),
					'cobertura' => __( 'Cobertura', 'pluma-engine' ),
					'audiencia' => __( 'Audiencia', 'pluma-engine' ),
				),
			),
			'vistaPrevia'         => array(
				'titulo'           => __( 'Vista previa en vivo', 'pluma-engine' ),
				'generando'        => __( 'Redactando con esta conducta…', 'pluma-engine' ),
				'errorPresupuesto' => __( 'Presupuesto diario agotado — la vista previa se pausa igual que la producción real.', 'pluma-engine' ),
				'errorGeneral'     => __( 'No se pudo generar la vista previa. Reintenta en unos segundos.', 'pluma-engine' ),
			),
			'guardarCambios'      => __( 'Guardar cambios', 'pluma-engine' ),
			'clonar'              => __( 'Clonar', 'pluma-engine' ),
			'nombreDelClon'       => __( 'Nombre del nuevo periodista clonado', 'pluma-engine' ),
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
