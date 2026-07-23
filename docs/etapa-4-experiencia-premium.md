# Etapa 4 — La experiencia premium

**Estado: EN CURSO.** Ninguna de las tres porciones entregadas hasta ahora está subida a `origin/main` todavía (commits locales `bea0a84`, `64008d3`, `8a50602` sobre `main`) — no hay CI verificado en este punto porque no se ha hecho push. Este documento se actualizará con cada porción nueva.

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Panel completo (cap. 10), onboarding 5 actos, estudio de conducta con vista previa, presupuestos de coste.
> **Criterio de salida**: usuario nuevo: instalación→primer borrador < 20 min sin documentación (test moderado real).

Las 7 pantallas del Cap. 10.2 del Libro de Arquitectura son: **Portada, Sala de Tendencias, Mesa Editorial, Banco de Periodistas, Sala de Revisión, Estudio SEO y Taxonomía, Sala de Máquinas** — más el onboarding de 5 actos (Cap. 10.3). Esta etapa se decidió entregar por **porciones verticales**: cada pantalla se construye completa (backend + frontend + tests) antes de pasar a la siguiente, en vez de construir todo el backend primero.

## Decisiones de producto tomadas al abrir la etapa (2026-07-23)

- **Orden de entrega**: slice vertical por pantalla, siguiendo el orden del Libro Cap. 10.2, con el onboarding al final — decisión explícita del propietario sobre la alternativa de "backend completo primero".
- **Vista previa en vivo del Estudio de Conducta** (todavía no construida): cuando llegue, disparará una llamada real al proveedor de lenguaje con *debounce* (~800ms) tras soltar el control deslizante, cacheada por combinación exacta de diales, **consumiendo del mismo presupuesto diario que la producción real** — sin bypass ni presupuesto separado, decisión explícita del propietario para no violar la regla dura de `PresupuestoLenguaje` ("se verifica ANTES de cada llamada, sin excepciones").

## Porción 1 — La Portada (commit `bea0a84`)

**Qué se agregó:**
- Shell del panel React (`Aplicacion.tsx`) con **barra de estado persistente** (modo activo, cuota del día, próxima publicación, coste contra presupuesto — Cap. 10.1: "el editor debe saber en tres segundos si todo está bien") y navegación por hash entre pantallas.
- `Pluma\Admin\PantallaSalud` **renombrada a `PantallaPanel`**: ahora es la página única de wp-admin que arranca el shell React completo (URL/nonce de REST + todos los textos i18n); la pantalla de salud original pasó a vivir dentro del shell como "Sala de Máquinas" (contenido parcial — solo versiones y estado del cron, no la bitácora/coste completos que el Cap. 10.2 exige para esa pantalla).
- `Pluma\Admin\RestPortada` (`GET /pluma/v1/panel/portada`, capacidad `pluma_configurar_motor`): modo de operación, cuota elástica de hoy contra la cola real, salud del motor (última ejecución + gasto del día), conteo exacto de piezas por estado (kanban compacto), alertas de retenidas/fallidas, tendencias por puntuación.
- Nuevos métodos de solo lectura sin cambio de esquema: `RepositorioTendencias::obtenerRecientes()`, `RepositorioBitacora::obtenerUltima()`, `RepositorioPiezas::contarPorEstado()`.
- Estilos editoriales propios (serif para titulares, sans para datos, color con significado, modo oscuro nativo vía `prefers-color-scheme`, responsivo móvil) — cero dependencia externa en esta porción.

**Decisión de diseño relevante:** "Resultados de ayer" (tráfico, piezas top, comentarios — Cap. 10.2) se dejó **fuera a propósito**: no existe todavía ninguna fuente real de tráfico en PLUMA (eso es el bucle de Search Console de la Etapa 5, PLUMA-E3-5) y "cero invención" prohíbe fabricar esa cifra. No se registró como deuda nueva porque directamente no aplica hasta que exista esa fuente de datos — se retomará junto con PLUMA-E3-5.

## Porción 2 — Sala de Tendencias (commit `64008d3`)

**Qué se agregó:**
- Tarjetas del radar con la Puntuación de Oportunidad desglosada en sus componentes reales (velocidad y afinidad) — **la propia tarjeta declara en pantalla** que hueco competitivo y vida útil llegan con el Radar completo (deuda PLUMA-E1-1, todavía abierta), en vez de fingir un desglose completo.
- "Quién la está cubriendo ya" (artículos reales de las señales del Sensor).
- Tres acciones directas sobre la agenda, con semántica fijada por decisión del propietario (2026-07-23):
  - **Cubrir ahora**: prioriza la Pieza viva o crea una nueva ya prioritaria si la anterior fue descartada. Nueva columna `prioridad` en `pluma_piezas` — el Orquestador ordena cada lote por prioridad DESC antes que por antigüedad.
  - **Ignorar**: descarta la Pieza en curso vía `Transicionador` (con auditoría, actor `editor`) y saca la tarjeta de la Sala.
  - **Vigilar**: descarta la Pieza en curso (sin gastar investigación/redacción/APIs) pero mantiene la tendencia destacada en el radar para cubrirla después.
  - Una Pieza ya **PUBLICADA nunca se toca** por ninguna de las tres acciones.
- `Pluma\Admin\RestSalaTendencias` (`GET /pluma/v1/tendencias`, `POST /tendencias/{id}/{cubrir,ignorar,vigilar}`), capacidad `pluma_aprobar_piezas` (intervenir la agenda es decisión editorial, no configuración del motor).
- Esquema `0.8.0`: columna `estado` en `pluma_tendencias` (en_pipeline/ignorada/vigilada) y `prioridad` en `pluma_piezas` con índice `estado_prioridad`.
- `Pluma\Pipeline\GestorSalaTendencias`, `Pluma\Sensores\EstadoTendencia`, `Pluma\Pipeline\TendenciaNoEncontradaException`.

**Decisión no trivial:** la semántica de "Vigilar" no estaba definida en el Libro más allá de nombrarla — se evaluaron tres opciones (sacar del pipeline manteniendo el radar / pausar la Pieza donde esté / etiqueta puramente visual) y el propietario eligió la primera: simple, sin estados nuevos en la máquina de Pieza, y sin gastar recursos en una tendencia que todavía no se decide cubrir.

## Porción 3 — Mesa Editorial (commit `8a50602`)

**Qué se agregó:**
- Kanban de las 13 columnas del grafo de `EstadoPieza`, con tarjeta por Pieza (tendencia, periodista, tesis corta, tono).
- Al abrir una Pieza: expediente completo (fuentes con extracto, url, fecha y nivel de verificación), Ficha de Decisión Editorial, desglose de Compuertas (calidad/riesgo/originalidad con motivos), historial completo de borradores **con diff línea a línea entre ciclos** (`diff`/jsdiff — la **primera dependencia de producción real** que el panel empaqueta y distribuye; ver `LICENSES-THIRD-PARTY.md`, actualizado en esta porción para dejar de afirmar "sin dependencias de producción").
- Cuatro acciones, todas auditadas:
  - **Reasignar periodista** (bloqueado con 409 sobre Piezas publicadas/descartadas).
  - **Editar el borrador a mano**: crea un ciclo nuevo marcado `editadoManualmente`, nunca reescribe el historial existente.
  - **Descartar**.
  - **Forzar aprobación, limitada a RETENIDA**: es literalmente el botón "Aprobar" de `GestorSalaRevision` — el grafo del Transicionador rechaza con 409 cualquier otro estado de origen. Ninguna ruta de código publica saltándose Compuertas.
- `Pluma\Admin\RestMesaEditorial` (`GET /piezas/kanban`, `GET /piezas/{id}`, `POST /piezas/{id}/{reasignar,editar,aprobar,descartar}`), capacidad `pluma_aprobar_piezas`.
- `GestorSalaRevision::aprobar()`/`descartar()` generalizados con un parámetro `$origen` — la auditoría ahora distingue si la acción vino de la Sala de Revisión o de la Mesa Editorial, sin duplicar lógica.
- Esquema `0.9.0`: columna `editado_manualmente` en `pluma_borradores`.

**Decisiones tomadas al abrir esta porción (preguntadas explícitamente al propietario):**
1. **"Forzar aprobación" limitada a RETENIDA** (frente a la alternativa de una ruta de aprobación general): se eligió la opción segura porque una ruta que fuerce cualquier Pieza no terminal a APROBADA sin pasar por Compuertas es exactamente lo que CLAUDE.md prohíbe explícitamente.
2. **Alcance completo, incluyendo editar y diff** (frente a diferir esas dos features): el propietario pidió el alcance completo en una sola porción, por lo que esta porción es notablemente más grande que las dos anteriores.

## Pendiente dentro de esta Etapa

Pantallas del Cap. 10.2 sin construir todavía: **Banco de Periodistas + Estudio de Conducta** (la "pantalla estrella" — diales como controles deslizantes con vista previa en vivo, matriz de tonos editable, memoria navegable), **Sala de Revisión visual** (la superficie funcional/REST ya existe desde la Etapa 3 — falta el diseño premium del Cap. 10.2 y las notificaciones con enlaces de acción directa vía Telegram/Slack, hoy solo hay correo), **Estudio SEO y Taxonomía**, **Sala de Máquinas completa** (bitácora de ejecuciones, coste por pieza/día, estado de cada API, configuración técnica — hoy solo tiene versiones + estado del cron), y el **onboarding de 5 actos** (Cap. 10.3).

Deuda de etapas anteriores explícitamente asignada a la Etapa 4 y **todavía sin resolver por ninguna de las tres porciones**:

| Ticket | Deuda | Nota |
|---|---|---|
| PLUMA-E2-4 | Sin pantalla/endpoint para cargar la llave de OpenRouter | Bloquea que cualquier instalación nueva use redacción sintética real en vez de fallback mecánico — candidata a Sala de Máquinas u onboarding acto 2 |
| PLUMA-E3-1 | Sitemap de noticias con ping de indexación | Sin priorizar todavía |
| PLUMA-E3-6 | Modo pausa / modo respeto (toggle en el panel) | Sin priorizar todavía |
| PLUMA-E3-8 | Detección activa de WP-Cron real + guía de instalación por hosting | Portada ya muestra si el cron está configurado (heredado de Etapa 0), pero falta la guía activa |
| PLUMA-E3-4 (ver `docs/etapa-3-capa-competitiva.md`) | JSON-LD nunca se emite en el frontend — verificado como no pagado pese a que `docs/deuda.md` lo daba por hecho en H3 de la Etapa 3 | Descubierto durante la redacción de este documento, no durante código de la Etapa 4 |

## A tener en cuenta para otras fases

- **`react`, `react-dom` y `diff` son ahora dependencias de producción reales** del bundle del panel (`build/panel/`) — cualquier auditoría de licencias (`LICENSES-THIRD-PARTY.md`) debe seguir actualizándose cada vez que el panel incorpore una librería nueva, no solo el lado PHP.
- **El shell (`Aplicacion.tsx`) solo enlaza pantallas que existen de verdad** — cada porción nueva añade su propia entrada de navegación al terminar, nunca antes (cero enlaces muertos). Quien construya la próxima porción debe seguir este mismo patrón.
- **`PresupuestoLenguaje` sigue siendo un único pool diario compartido** (Etapa 2) — la vista previa en vivo del Estudio de Conducta, cuando se construya, debe seguir consumiendo de ahí; introducir un pool separado sería una regresión sobre la decisión ya tomada.
- **El renombre de `PantallaSalud` a `PantallaPanel`** (porción 1) es un punto de fricción si alguna documentación o memoria de sesiones anteriores todavía referencia el nombre viejo — verificado que no quedan referencias en `src/` al cierre de la porción 3.

## Evidencia de gates (acumulado, sin push todavía)

| Porción | Unit | Invariantes | Integration (wp-env real) | Vitest | PHPCS / PHPStan L8 |
|---|---|---|---|---|---|
| 1 — Portada | — | — | 46/46 | 19/19 | limpio |
| 2 — Sala de Tendencias | 299/299 | 21/21 | 52/52 | 24/24 | limpio |
| 3 — Mesa Editorial | 301/301 | 21/21 | 60/60 | 32/32 | limpio |

Build de producción del panel verificado (`npm run build`) al cierre de cada porción. Sin llave de API filtrada en ningún commit (verificado explícitamente antes de cada uno).
