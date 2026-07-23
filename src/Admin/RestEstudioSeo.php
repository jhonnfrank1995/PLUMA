<?php

declare(strict_types=1);

namespace Pluma\Admin;

use Pluma\Datos\RepositorioPiezasInterface;
use Pluma\Datos\RepositorioVocabularioInterface;
use Pluma\Kernel\Capacidades;
use Pluma\Taxonomia\ReconciliadorVocabulario;
use Pluma\Taxonomia\TipoVocabulario;
use WP_REST_Response;

/**
 * Estudio SEO y Taxonomía (Libro Cap. 10.2): auditoría de canibalización y
 * salud taxonómica. Protegida con `pluma_configurar_motor` — mismo criterio
 * que la Sala de Máquinas: visibilidad técnica del motor, no aprobación
 * editorial.
 *
 * "Estado de indexación por pieza" y "keywords en el umbral 5-15" quedan
 * fuera deliberadamente: dependen de Google Search Console, que no existe
 * todavía (`PLUMA-E3-5`) — cero invención, igual que en la Portada y el
 * Banco de Periodistas. Esta pantalla es de solo lectura: muestra las
 * propuestas de fusión de etiquetas, no las ejecuta (fusionar de verdad
 * implicaría reasignar términos en posts ya publicados).
 */
final class RestEstudioSeo {

	private const RUTA_CANIBALIZACION = '/seo/canibalizacion';
	private const RUTA_VOCABULARIO    = '/seo/vocabulario';

	public function __construct(
		private readonly RepositorioPiezasInterface $piezas,
		private readonly RepositorioVocabularioInterface $vocabulario,
		private readonly ReconciliadorVocabulario $reconciliador,
	) {
	}

	public function registrar(): void {
		add_action( 'rest_api_init', array( $this, 'registrarRutas' ) );
	}

	public function registrarRutas(): void {
		register_rest_route(
			'pluma/v1',
			self::RUTA_CANIBALIZACION,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'canibalizacion' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);

		register_rest_route(
			'pluma/v1',
			self::RUTA_VOCABULARIO,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'vocabulario' ),
				'permission_callback' => array( $this, 'autorizado' ),
			)
		);
	}

	public function autorizado(): bool {
		return current_user_can( Capacidades::CONFIGURAR_MOTOR );
	}

	public function canibalizacion(): WP_REST_Response {
		$grupos = array();

		foreach ( $this->piezas->obtenerCanibalizacion() as $grupo ) {
			$piezas = array();

			foreach ( $grupo['piezaIds'] as $piezaId ) {
				$pieza = $this->piezas->obtenerPorId( $piezaId );

				if ( null === $pieza || null === $pieza->postId ) {
					continue;
				}

				$piezas[] = array(
					'piezaId' => $pieza->id,
					'titulo'  => get_the_title( $pieza->postId ),
					'url'     => get_permalink( $pieza->postId ),
				);
			}

			if ( count( $piezas ) < 2 ) {
				continue;
			}

			$grupos[] = array(
				'keywordPrincipal' => $grupo['keywordPrincipal'],
				'piezas'           => $piezas,
			);
		}

		return new WP_REST_Response( $grupos, 200 );
	}

	public function vocabulario(): WP_REST_Response {
		$entradas = array_merge(
			$this->vocabulario->obtenerPorTipo( TipoVocabulario::Categoria ),
			$this->vocabulario->obtenerPorTipo( TipoVocabulario::Etiqueta )
		);

		$cuarentena = array();
		$candidatas = array();

		foreach ( $entradas as $entrada ) {
			if ( $entrada->enCuarentena ) {
				$cuarentena[] = array(
					'id'         => $entrada->id,
					'tipo'       => $entrada->tipo->value,
					'nombre'     => $entrada->nombre,
					'vecesUsada' => $entrada->vecesUsada,
				);

				continue;
			}

			$candidatas[] = $entrada;
		}

		$propuestasFusion = array();
		$total            = count( $candidatas );

		for ( $i = 0; $i < $total; $i++ ) {
			for ( $j = $i + 1; $j < $total; $j++ ) {
				$a = $candidatas[ $i ];
				$b = $candidatas[ $j ];

				if ( $a->tipo !== $b->tipo || $a->slug === $b->slug ) {
					continue;
				}

				$similitud = $this->reconciliador->similitud( $a->nombre, $b->nombre );

				if ( $similitud >= ReconciliadorVocabulario::UMBRAL_SIMILITUD_PORCENTAJE ) {
					$propuestasFusion[] = array(
						'tipo'      => $a->tipo->value,
						'idA'       => $a->id,
						'nombreA'   => $a->nombre,
						'idB'       => $b->id,
						'nombreB'   => $b->nombre,
						'similitud' => round( $similitud, 1 ),
					);
				}
			}
		}

		return new WP_REST_Response(
			array(
				'cuarentena'       => $cuarentena,
				'propuestasFusion' => $propuestasFusion,
			),
			200
		);
	}
}
