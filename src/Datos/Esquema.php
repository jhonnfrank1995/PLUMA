<?php

declare(strict_types=1);

namespace Pluma\Datos;

use wpdb;

/**
 * Definiciones de esquema de la Etapa 1 (sub-agente ESQUEMA).
 *
 * Formato estricto de `dbDelta`: cada columna en su propia línea, dos
 * espacios antes de `PRIMARY KEY`, sin comillas ni backticks en nombres.
 * Índices en todo campo de estado+fecha — el motor consulta siempre
 * "dame N piezas en estado X por prioridad" (CLAUDE.md § Orquestador).
 */
final class Esquema {

	/**
	 * @return list<string>
	 */
	public static function sentenciasCreateTable( wpdb $wpdb ): array {
		$prefijo = $wpdb->prefix . 'pluma_';
		$charset = $wpdb->get_charset_collate();

		return array(
			"CREATE TABLE {$prefijo}tendencias (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                termino VARCHAR(191) NOT NULL,
                fuente_senal VARCHAR(50) NOT NULL,
                puntuacion_velocidad DECIMAL(5,2) NOT NULL,
                puntuacion_afinidad DECIMAL(5,2) NOT NULL,
                puntuacion_total DECIMAL(5,2) NOT NULL,
                articulos_relacionados LONGTEXT NOT NULL,
                detectada_en DATETIME NOT NULL,
                creada_en DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY termino_fuente (termino(100), fuente_senal),
                KEY puntuacion_total (puntuacion_total)
            ) {$charset};",
			"CREATE TABLE {$prefijo}piezas (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                tendencia_id BIGINT UNSIGNED NOT NULL,
                estado VARCHAR(30) NOT NULL,
                expediente LONGTEXT NULL,
                post_id BIGINT UNSIGNED NULL,
                creada_en DATETIME NOT NULL,
                actualizada_en DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY estado_actualizada (estado, actualizada_en),
                KEY tendencia_id (tendencia_id)
            ) {$charset};",
			"CREATE TABLE {$prefijo}fuentes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                pieza_id BIGINT UNSIGNED NOT NULL,
                url VARCHAR(2000) NOT NULL,
                extracto TEXT NOT NULL,
                nivel_verificacion VARCHAR(20) NOT NULL,
                fecha DATETIME NOT NULL,
                creada_en DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY pieza_id (pieza_id)
            ) {$charset};",
			"CREATE TABLE {$prefijo}bitacora_motor (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                iniciada_en DATETIME NOT NULL,
                finalizada_en DATETIME NULL,
                lotes_procesados INT UNSIGNED NOT NULL DEFAULT 0,
                errores LONGTEXT NULL,
                candado_adquirido TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY  (id),
                KEY iniciada_en (iniciada_en)
            ) {$charset};",
			"CREATE TABLE {$prefijo}auditoria (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                pieza_id BIGINT UNSIGNED NOT NULL,
                estado_anterior VARCHAR(30) NULL,
                estado_nuevo VARCHAR(30) NOT NULL,
                actor VARCHAR(20) NOT NULL,
                motivo VARCHAR(255) NOT NULL,
                ocurrida_en DATETIME NOT NULL,
                PRIMARY KEY  (id),
                KEY pieza_id (pieza_id),
                KEY ocurrida_en (ocurrida_en)
            ) {$charset};",
		);
	}

	/**
	 * @return list<string> nombres de tabla completos (con `$wpdb->prefix`) para la reversa de desinstalación
	 */
	public static function nombresTablas( wpdb $wpdb ): array {
		$prefijo = $wpdb->prefix . 'pluma_';

		return array(
			$prefijo . 'tendencias',
			$prefijo . 'piezas',
			$prefijo . 'fuentes',
			$prefijo . 'bitacora_motor',
			$prefijo . 'auditoria',
		);
	}

	/**
	 * Reversa de {@see sentenciasCreateTable()}: solo se invoca cuando el
	 * cliente eligió explícitamente NO conservar datos (GOVERNANCE §5.4).
	 */
	public static function eliminarTablas( wpdb $wpdb ): void {
		foreach ( self::nombresTablas( $wpdb ) as $tabla ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- nombre de tabla generado internamente, no hay identificador parametrizable en $wpdb->prepare().
			$wpdb->query( "DROP TABLE IF EXISTS {$tabla}" );
		}
	}
}
