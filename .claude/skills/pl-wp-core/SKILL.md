---
name: pl-wp-core
description: PLUMA — Estándares de integración con WordPress. Usar ante cualquier hook, endpoint REST, capacidad, tabla dbDelta, transient, i18n, encolado de assets, activación/desactivación/desinstalación, multisitio o convivencia con otros plugins (Yoast, Rank Math, cachés).
---

# PLUMA — Núcleo WordPress

## Contratos innegociables
1. **E/S**: sanitizar toda entrada, escapar toda salida, `$wpdb->prepare` siempre, nonce + capacidad `pluma_*` en todo endpoint (`permission_callback` real). PHPCS WordPress.Security a 0 — es gate, no sugerencia.
2. **REST**: namespace `pluma/v1`, respuestas `{data, meta, errors}`, códigos HTTP correctos, jamás excepción cruda al cliente. Todo endpoint nace con test de integración (incluida capacidad denegada).
3. **Tablas**: dbDelta idempotente + `pluma_db_version` + migraciones con reversa (sub-agente ESQUEMA). El pipeline vive en tablas `pluma_*`; `wp_posts` solo recibe el resultado final.
4. **Hooks propios**: `pluma/` + snake_case, documentados en `docs/hooks.md` con firma y estabilidad (público/interno). Los hooks públicos son API de venta: romperlos es breaking change SemVer.
5. **Assets**: solo en pantallas PLUMA (`admin_enqueue_scripts` con guard), build de Vite con manifest, jamás dev server en producción. Frontend público ≈ 0 peso.
6. **i18n**: text-domain `pluma-engine` en toda cadena; `wp i18n make-pot` en el build; fechas/números vía funciones de WP con locale.
7. **Ciclo de vida**: activación crea esquema+capacidades+guía de cron; desactivación pausa el motor sin borrar nada; `uninstall.php` respeta "conservar datos" (defecto: sí — el banco de periodistas del cliente jamás se borra por accidente). Multisitio: por-blog, con activación de red soportada.
8. **Convivencia**: detección de Yoast/Rank Math (escribir en sus campos, no duplicar), purga de cachés de página del post publicado vía sus hooks si existen, Action Scheduler si está presente (no se requiere, no se colisiona).
9. **Dependencias**: Composer con PHP-Scoper — jamás exponer vendors sin prefijar en un plugin comercial (colisiones = tickets de soporte infinitos).
