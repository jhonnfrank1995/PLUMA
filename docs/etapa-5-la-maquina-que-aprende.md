# Etapa 5 — La máquina que aprende

**Estado: EN CURSO.** Porción 1 (Bucle de Search Console) completa y commiteada localmente, pendiente de push y verificación de CI.

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Bucle de Search Console, memoria de audiencia, piezas de refuerzo y actualización ("dos golpes"), respuestas a comentarios asistidas, informes editoriales semanales.
> **Criterio de salida**: el sistema propone decisiones (refuerzos, ajustes de asignación) basadas en datos reales del sitio.

Esta etapa tiene 4 piezas grandes e independientes entre sí (Libro Cap. 6.4 y `PLAN-MAESTRO.md`). El propietario decidió empezar por el **bucle de Search Console** — la más grande y riesgosa (primer cliente OAuth2 completo del proyecto), pero la que paga más deuda (`PLUMA-E3-5`) y de la que dependen parcialmente las demás.

## Decisiones de producto tomadas al abrir la etapa (2026-07-23)

- **Orden de entrega**: bucle de Search Console primero, seguido de piezas de refuerzo/"dos golpes", memoria de audiencia + respuestas asistidas a comentarios, e informes editoriales — decisión explícita del propietario sobre las otras dos alternativas propuestas (empezar por memoria de audiencia, o por la deduplicación semántica de `PLUMA-E1-2`).
- **Detección de proveedor de hosting para el Acto 1 del onboarding** (Etapa 4): no aplica aquí, ya resuelto.
- **Alcance real de la API externa de Google, verificado contra documentación oficial antes de escribir cualquier línea de código** (cero invención): endpoints OAuth2 (`accounts.google.com/o/oauth2/v2/auth`, `oauth2.googleapis.com/token`), scope de solo lectura (`webmasters.readonly`), y forma exacta de `sites.list`/`searchAnalytics.query` — todo verificado vía `WebFetch` contra `developers.google.com` antes del plan técnico.

## Porción 1 — Bucle de Search Console (commit pendiente)

**Qué se agregó:**
- **Conexión OAuth2 real**: cada instalación de PLUMA usa su PROPIO client_id/client_secret de un proyecto de Google Cloud del propietario del sitio (igual que la llave de OpenRouter) — un secreto compartido embebido en un plugin distribuido sería una fuga de credenciales entre clientes. El flujo completo: guardar credenciales → mostrar la URI de redirección exacta a registrar en Google Cloud → redirección real a Google → callback con verificación de `state` (anti-CSRF) → intercambio de código por `access_token`/`refresh_token` → refresh_token cifrado guardado, access_token cacheado en un transient de corta vida (nunca persistido).
- **Selección del sitio verificado**: `GET /search-console/sitios` lista los sitios reales a los que el usuario de Google autenticado tiene acceso (`sites.list`); el propietario elige cuál corresponde a esta instalación.
- **Sincronización real de métricas**: `POST /search-console/sincronizar` consulta `searchAnalytics.query` (últimos 28 días, dimensiones página+consulta) y guarda clics/impresiones/CTR/posición reales en `pluma_metricas_search_console`, resolviendo cada página a su Pieza real vía `url_to_postid()` (WordPress core) + nuevo `RepositorioPiezasInterface::obtenerPorPostId()`. `pieza_id` queda `NULL` cuando la URL no pertenece a ninguna Pieza gestionada por PLUMA (contenido ajeno del sitio) — dato real, no se descarta ni se inventa una atribución.
- `Pluma\Proveedores\ProveedorSearchConsole` (+ DTOs `TokensOAuth`, `FilaAnaliticaSearchConsole`, excepción `ProveedorSearchConsoleException`): mismo patrón de resiliencia que `ProveedorGoogleTrends`/`ProveedorOpenRouter` — circuit breaker por fallos consecutivos, timeout explícito, sin `sleep()` síncrono.
- `Pluma\Admin\RestSearchConsole`, capacidad `pluma_configurar_motor` (mismo criterio que la Sala de Máquinas: configuración técnica del motor). El callback OAuth2 (`GET /search-console/callback`) tiene un `permission_callback` real (`current_user_can`, nunca `__return_true`) porque la cookie de sesión del administrador viaja en la redirección del navegador — la verificación de `state` es una segunda capa anti-CSRF, no la única.
- `panel/src/BloqueSearchConsole.tsx`: formulario de credenciales, URI de redirección a copiar, selector de sitio, botón de sincronización manual, tabla de métricas recientes — integrado en la Sala de Máquinas junto al bloque de llave de OpenRouter.
- Esquema `0.10.0`: nueva tabla `pluma_metricas_search_console`.

**Honestidad de alcance (cero invención), decidida antes de abrir esta porción:** los CONSUMIDORES del dato real (regenerar titulares SEO de piezas con impresiones altas y CTR bajo, candidatos a "pieza de refuerzo" desde keywords en posiciones 5-15, ajuste de asignación de periodistas por rendimiento real, el componente de "hueco competitivo" de `PuntuacionOportunidad`) quedan **deliberadamente fuera** de esta porción — no existe todavía suficiente dato real sincronizado para construir esa lógica de decisión sin inventar reglas arbitrarias. Registrados como deuda nueva `PLUMA-E5-1` para porciones futuras de esta misma Etapa.

**Hallazgo real durante la verificación:** un cliente OAuth2 completo era territorio nuevo para el proyecto — cero precedente de manejo de `access_token`/`refresh_token` en el código existente (confirmado por grep antes de diseñar). Se verificaron los tres endpoints reales de Google (autorización, token, Search Console API) contra `developers.google.com` vía `WebFetch` antes de escribir el proveedor, evitando alucinar cualquier firma de API.

**Decisión técnica no trivial, corregida durante la implementación:** el callback OAuth2 inicialmente usaba `wp_safe_redirect()` + `exit` (patrón común pero intestable: `exit` mata el proceso de PHPUnit si el método se invoca en un test). Corregido devolviendo un `WP_REST_Response` con cabecera `Location` y estado 302 — `WP_REST_Server::serve_request()` envía esas cabeceras al navegador real exactamente igual, pero el método queda invocable y verificable en `RestSearchConsoleTest`.

## Pendiente dentro de esta Etapa

- **Piezas de refuerzo y "dos golpes"** (Libro Cap. 3.4, 6.4): requiere además pagar `PLUMA-E1-2` (huella semántica de tendencias, abierta desde la Etapa 1) para detectar que una historia ya cubierta "evoluciona".
- **Memoria de audiencia + respuestas asistidas a comentarios** (paga `PLUMA-E2-3`): `TipoMemoria::Audiencia` ya existe en el enum pero nunca se escribe ni se lee; el plugin todavía no lee comentarios reales de WordPress (`get_comments()`) en ningún lugar.
- **Informes editoriales semanales**: no existe ninguna pantalla ni generador de informes todavía en `Pluma\Admin`.
- **Consumidores del dato de Search Console** (`PLUMA-E5-1`, registrada en esta porción): regenerar títulos débiles, candidatos de refuerzo, ajuste de asignación por periodista, hueco competitivo real en `PuntuacionOportunidad`.

## A tener en cuenta para otras fases

- **Cada integración OAuth2/API externa nueva debe usar credenciales propias por instalación**, nunca un secreto compartido embebido en el plugin distribuido — mismo principio ya aplicado a la llave de OpenRouter, ahora también a Search Console. Cualquier integración futura (Analytics, redes sociales, etc.) debe seguir el mismo patrón.
- **`exit` después de `wp_safe_redirect()` dentro de un callback REST mata el proceso de PHPUnit si el método se invoca directamente en un test** — la alternativa correcta y verificada es devolver un `WP_REST_Response` con cabecera `Location` y el código de estado 3xx; `WP_REST_Server::serve_request()` ya envía esas cabeceras al navegador real. Cualquier futuro callback OAuth (o cualquier redirección desde un endpoint REST) debe seguir este patrón, no `wp_safe_redirect()`+`exit`.
- **`add_query_arg()` espera valores ya codificados por el llamador** (`urlencode()`/`rawurlencode()`) — verificado contra la documentación oficial antes de usarlo en `ProveedorSearchConsole::urlAutorizacion()`. No es automático.
- **Al construir una URL de redirección que además necesita un fragmento hash del panel** (`#/salud`), el fragmento debe ir SIEMPRE al final, después de `add_query_arg()` sobre la URL base sin `#` — anteponerlo produce un query arg anexado dentro del fragmento, invisible para PHP y semánticamente incorrecto. Corregido en `RestSearchConsole::callback()` antes de cerrar esta porción.
- **Cada tabla nueva en `Esquema::sentenciasCreateTable()` cambia el conteo total de tablas** que `ActivadorTest.php` espera exactamente vía Mockery (`Functions\expect('dbDelta')->times(N)`) — hay que actualizar esos conteos exactos (11→12 en esta porción) cada vez que se agregue una tabla, o los tests de Unit fallan con "should be called exactly N times but called N+1 times".

## Evidencia de gates

| Porción | Unit | Invariantes | Integration (wp-env real) | Vitest | E2E | PHPCS / PHPStan L8 | Push + CI |
|---|---|---|---|---|---|---|---|
| 1 — Bucle de Search Console | 324/324 | 21/21 | 106/106 | 67/67 | 2/2 | limpio | commiteado, sin push todavía |

Build de producción del panel verificado (`npm run build`) al cierre de la porción. Sin credenciales reales (llave de OpenRouter ni client_secret de Google) filtradas en ningún commit (verificado explícitamente antes de cada uno).
