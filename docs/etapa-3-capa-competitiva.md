# Etapa 3 — La capa competitiva

**Estado:** Cerrada el 2026-07-23 · CI en verde en GitHub Actions (run [30008246143](https://github.com/jhonnfrank1995/PLUMA/actions/runs/30008246143)).

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Motor SEO (+ convivencia Yoast/Rank Math), Taxónomo, tres compuertas, Copiloto y Autónomo con degradación, sala de revisión + notificaciones.
> **Criterio de salida**: una semana en Copiloto sin corrección posterior; suite de invariantes y matriz de compatibilidad SEO en verde.

Esta fue la primera etapa completa dentro de esta sesión de trabajo (la parte final de su construcción y cierre) — por eso este documento sí incluye historial real de depuración y decisiones de diseño con su razonamiento, no solo lo registrado en `CHANGELOG.md`.

## Qué se agregó

### `Pluma\Compuertas` (H1)
`CompuertaCalidad` (reaprovecha el juicio del Corrector Interno + veto duro por falta de sustento — una pieza sin trazabilidad al expediente NUNCA aprueba sin importar su puntuación), `CompuertaRiesgo` (sensibilidad temática con degradación automática de modo, difamación y hechos disputados → RETENIDA para humano, temas legalmente regulados), `CompuertaOriginalidad` (solapamiento n-grama contra fuentes y contra el propio sitio, huella de ganancia de información), `GestorDegradacion` (Autónomo→Copiloto por sensibilidad, Piloto forzado ante menores — ninguna configuración de usuario lo anula), `EvaluadorCompuertas` como **único camino legal** hacia Publicación.

### `Pluma\Seo` (H2)
`ExtractorPalabrasClave`, `GeneradorMetadatosSeo` (doble titular: editorial con voz del periodista + SEO orientado a búsqueda, en una sola llamada consolidada), `ConstructorEsquemaNewsArticle` (JSON-LD NewsArticle/OpinionNewsArticle/AnalysisNewsArticle según el tono, verificado contra schema.org y la guía de Google), `DetectorPluginSeo` (Yoast/Rank Math verificados contra su código fuente oficial — PLUMA escribe en sus campos reales, nunca duplica su capa), `EnlazadorInterno`, `AuditorCanibalizacion`, `MotorSeo` como orquestador único.

### `Pluma\Taxonomia` (H2)
`ExtractorEntidades` (heurística determinista de secuencias capitalizadas), `ReconciliadorVocabulario` (coincidencia exacta, alias, similitud — nunca fragmenta etiquetas), `GestorEtiquetas` (etiquetas nuevas nacen en cuarentena hasta 3+ piezas), `AsignadorCategoria` (jamás crea una categoría — solo asigna de las que el editor ya definió).

### `Pluma\Pipeline\Orquestador` real (H3)
Cadencia elástica (cuota objetivo/mínima/máxima), ventanas horarias con peso, separación mínima + jitter, topes por vertical y por periodista (`ProgramadorCadencia`, `LectorConfiguracionCadencia`, tabla `pluma_cola_publicacion`); modos Piloto/Copiloto/Autónomo reales con ventana de veto configurable en Copiloto; escasez honesta (nunca rebaja umbrales para rellenar cuota, registra el déficit).

### Publicación y Sala de Revisión (H3)
`Pluma\Publicacion\Publicador` (convierte el borrador en post publicado real, aplicando SEO y taxonomía ya calculadas); `Pluma\Pipeline\GestorSalaRevision` + `Pluma\Admin\RestSalaRevision` (bandeja de RETENIDAS y cola de veto de Copiloto, tres acciones: `aprobar`/`devolver con nota`/`descartar`, capacidad `pluma_aprobar_piezas`); `Pluma\Admin\NotificadorRevision` (`wp_mail` cuando una pieza queda retenida).

### Esquema y tests
Esquema `0.7.0`: tablas `pluma_vocabulario` y `pluma_cola_publicacion`; `pluma_piezas` ampliada con `modo_efectivo`, `diagnostico_compuertas`, `keyword_principal`, `datos_seo`, `resultado_taxonomia`. `tests/Invariantes` cierra GOVERNANCE §2 completo: §2.1 (toda decisión de compuerta se persiste y audita), §2.2 segunda capa (bloqueo de sátira también en Compuertas), §2.3 (difamación → RETENIDA sin importar el modo), §2.7 (escasez honesta nunca toca los umbrales).

## Qué se corrigió / decisiones no triviales

- **El Orquestador no invocaba nada de H1/H2 al empezar H3.** Se detectó a mitad de la etapa que el pipeline nunca llamaba a Compuertas/SEO/Taxónomo pese a que ambos módulos ya existían — se diseñaron e implementaron 5 etapas nuevas del tick (`procesarOptimizacion`, `procesarCompuertas`, `procesarProgramacion`, `procesarPublicacionesVencidas`, `verificarEscasezHonesta`) para cerrar ese hueco antes de dar la etapa por completa.
- **Cuota elástica vs. rígida**: decisión del propietario — cuota con mínimo/máximo configurables (rígida = fijar mínimo igual a máximo), no un número único.
- **Ventana de veto de Copiloto**: decisión del propietario — configurable desde una pestaña de ajustes (sin un valor por defecto impuesto por el agente más allá de una recomendación de fábrica de 2 horas).
- **Rediseño del grafo del Transicionador para la Sala de Revisión**: la arista `retenida` pasó de `{en_revision, descartada}` a `{optimizada, aprobada, descartada}`. Razonamiento: `en_revision` es un estado transitorio y atómico dentro de una sola llamada del Orquestador — nadie vuelve a sondearlo por sí solo, así que reingresar ahí desde un actor externo (humano vía REST) dejaría la pieza varada para siempre. `optimizada` sí es sondeada en cada tick por `avanzarPipeline()`, así que "devolver con nota" fuerza una re-evaluación real por Compuertas. `aprobada` desde `retenida` es la anulación humana informada que el Libro Cap. 8.2 exige ("RETENIDA para humano" implica que el humano ES la autoridad final de ese caso, no un atajo que salta Compuertas).
- **Diseño de capas de EscritorCamposSeo (Publicacion)**: en vez de depender directamente de `Pluma\Seo\DetectorPluginSeo` (capa no adyacente a Publicacion), `EscritorCamposSeo` recibe el `TipoPluginSeo` YA calculado y persistido en `DatosSeo` — evita contaminación cruzada de capas no adyacentes.
- **Hallazgo técnico real y verificado contra WordPress core**: `WP_UnitTestCase::set_up()` instala un filtro (`_create_temporary_tables`) que convierte cualquier `CREATE TABLE` ejecutado después de ese punto en `CREATE TEMPORARY TABLE` — y estas SÍ participan del `ROLLBACK` transaccional entre tests, a diferencia de las tablas reales. Causó un fallo real al añadir `pluma_vocabulario` (la primera tabla genuinamente nueva del entorno de pruebas): existía justo después de `Activador::activar()` pero desaparecía antes del siguiente test. Solución adoptada (verificada contra el propio test de `dbDelta` del núcleo de WordPress): activar el esquema en un método estático `set_up_before_class()`, antes de que el filtro exista — documentado en `docs/skills-descubiertas.md` y aplicado también a `pluma_cola_publicacion`.
- **Bug recurrente de Mockery (expectativas)**: definir un `->allows('metodo')->andReturn(...)` genérico ANTES de un `->expects('metodo')->with(...)` específico para el mismo método hace que el genérico gane siempre — Mockery usa la PRIMERA expectativa que matchea, no la más específica. Corregido reordenando: expectativas específicas primero, catch-all genérico al final, en `OrquestadorTest.php` y en los tests de invariantes.
- **Corrupción de codificación por PowerShell**: `Set-Content -Encoding utf8` añadió BOM UTF-8 y corrompió caracteres acentuados (mojibake) al reescribir un test grande. Corregido manualmente carácter por carácter y despojando el BOM.

## Deuda técnica de esta etapa

| Ticket | Deuda | Pago asignado |
|---|---|---|
| PLUMA-E3-1 | Sitemap de noticias (protocolo Google News, últimas 48h) con ping de indexación | Etapa 4 — **sigue abierta** al cierre de la porción 3; no priorizada todavía en ninguna porción |
| PLUMA-E3-2 | Imagen destacada generada/seleccionada con banco de licencia y compresión WebP | Etapa futura sin asignar — depende de un `Pluma\Proveedores\ProveedorImagenInterface` inexistente |
| PLUMA-E3-3 | Reformulación de H2/H3 como preguntas ("People Also Ask") en `GeneradorEsqueleto` | Etapa futura sin asignar |
| PLUMA-E3-4 | `ConstructorEsquemaNewsArticle` no tiene todavía hook de render en el frontend (`wp_head`) que lo invoque con datos reales del post | `docs/deuda.md` la había marcado como pago previsto dentro de la propia Etapa 3, H3 ("mismo paquete de wiring que el Publicador real") — **verificado en esta revisión: NO se pagó**. `grep` confirma que `ConstructorEsquemaNewsArticle` no se referencia desde ningún archivo de `src/Publicacion/`; sigue siendo una función pura sin ningún punto de invocación real. **Reabierta el 2026-07-23 en `docs/deuda.md`**, sin etapa de pago asignada todavía |
| PLUMA-E3-5 | Bucle de retroalimentación con Search Console (impresiones sin clics, keywords 5–15, rendimiento por periodista) | Etapa 5 — sin tocar, en su etapa correcta |
| PLUMA-E3-6 | "Modo pausa" (pausa global de un clic) y "modo respeto" (congela humor/sátira ante tragedia mayor) — requieren un toggle en el panel que todavía no existía | Etapa 4 (panel de administración) — **sigue abierta**; ninguna de las tres porciones entregadas hasta ahora la construyó |
| PLUMA-E3-7 | Reintentos con retroceso exponencial ante fallos de proveedores externos; alertas tras 3 fallos | Etapa futura sin asignar — no bloquea el criterio de salida de la Etapa 3 |
| PLUMA-E3-8 | Detección activa de WP-Cron real vs. cron del servidor + guía de instalación por hosting | Etapa 4 (Cap. 10.3, experiencia de instalación) — **sigue abierta**; la Portada de la Etapa 4 SÍ muestra si el cron real está configurado (heredado de la pantalla de Salud de la Etapa 0), pero no hay todavía guía de instalación guiada por hosting detectado |

## A tener en cuenta para otras fases

- **⚠️ PLUMA-E3-4 quedó marcada implícitamente como resuelta pero no lo está** (ver verificación arriba): el JSON-LD de `Pluma\Seo\ConstructorEsquemaNewsArticle` nunca se emite en una página real. Reabierta el 2026-07-23 en `docs/deuda.md`, todavía sin etapa de pago asignada — candidata natural a agruparse con `PLUMA-E3-1` (sitemap de noticias), ambas emisión real de metadatos SEO en el frontend público.
- **PLUMA-E3-6 (modo pausa/modo respeto) y PLUMA-E3-1 (sitemap) son las deudas de Etapa 3 más directamente asignadas a la Etapa 4** y ninguna de las tres porciones entregadas hasta ahora las resolvió — son candidatas naturales para la porción de "Sala de Máquinas" (configuración técnica) o para un ajuste rápido del Orquestador expuesto vía panel.
- **El patrón `set_up_before_class()` para tablas nuevas en tests de Integración es obligatorio de aquí en adelante** — se aplicó correctamente a `pluma_vocabulario` y `pluma_cola_publicacion` (Etapa 3) y a todas las tablas nuevas de la Etapa 4 (ver ese documento). Cualquier tabla nueva futura debe seguir este mismo patrón o reproducirá el mismo fallo silencioso.
- **El orden `retenida → {optimizada, aprobada, descartada}` del Transicionador es el cimiento exacto** sobre el que la Mesa Editorial de la Etapa 4 construyó "forzar aprobación limitada a RETENIDA" — cualquier cambio a ese grafo afecta directamente esa función del panel.
- **La regla "ninguna ruta de código publica una Pieza bajo el umbral de Compuertas"**, ya viva en el Orquestador desde esta etapa, se convirtió en la base de la decisión explícita de la Etapa 4 de NO construir una "aprobación forzada general" — solo reutilizar `GestorSalaRevision::aprobar()` sobre RETENIDA.

## Evidencia de gates al cierre

315 tests Unit+Invariantes, 41 tests de Integración contra wp-env real, 4 tests Vitest, build de producción verificado, PHPCS y PHPStan nivel 8 limpios — los 4 jobs de CI en `success`.
