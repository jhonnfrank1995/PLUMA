# Etapa 4 — La experiencia premium

**Estado: EN CURSO.** Tres porciones subidas a `origin/main` y con CI verde confirmado (`bea0a84`, `64008d3`, `8a50602`, más el fix de una regresión de E2E propia — run [30025921402](https://github.com/jhonnfrank1995/PLUMA/actions/runs/30025921402) falló, corregido en `c45494b` con el siguiente run [30026606648](https://github.com/jhonnfrank1995/PLUMA/actions/runs/30026606648) en verde — y la documentación por fase en `5b0482b`). La porción 4 (commit `c30f6ea`) está commiteada localmente, todavía sin push ni CI verificado en este punto. Este documento se actualiza con cada porción nueva.

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

## Porción 4 — Banco de Periodistas + Estudio de Conducta (commit `c30f6ea`)

**Qué se agregó:**
- Tarjetas del banco con métricas vivas **reales**: piezas publicadas (COUNT exacto) y verticales donde más publica (extraídos de `clasificacion.tema` de la Ficha de Decisión Editorial de sus Piezas publicadas, top 3 por frecuencia). "Tráfico medio" del Cap. 10.2 queda fuera — sin fuente real todavía, mismo criterio que "resultados de ayer" en la Portada (porción 1).
- Acciones del banco: **crear desde plantilla** (los tres periodistas de siembra del Cap. 5.8 — analista/columnista/cronista), **clonar** (copia identidad + conducta actual bajo un nombre nuevo), **jubilar** — las tres construidas enteramente sobre métodos ya existentes de `RepositorioPeriodistasInterface` (`crear()`, `jubilar()`), sin duplicar lógica.
- El **Estudio de Conducta**: diales de temperamento como controles deslizantes, reglas cualitativas editables (línea editorial, líneas rojas, muletillas, vocabulario prohibido, trato al lector, estilo de pregunta final), matriz de tonos editable para los 4 tipos de noticia normales — la fila de `TipoNoticia::Tragedia` se muestra siempre bloqueada y de solo lectura, la misma regla de sistema inviolable del Cap. 5.3 (sátira bloqueada) — y memoria editorial reciente navegable.
- **Vista previa en vivo** (Libro Cap. 10.2: "la función que enamora en las demos"): `Pluma\Redaccion\GeneradorVistaPrevia` redacta un párrafo corto sobre un **hecho neutro fijo** (nunca una Pieza ni un expediente real — esto es una demostración de conducta) usando la conducta CANDIDATA todavía sin guardar. Nuevo `PropositoLenguaje::VistaPrevia`, deliberadamente no premium (modelo económico). El frontend hace debounce de 800ms tras el último cambio y no repite la llamada si la combinación exacta de diales/reglas/matriz ya se pidió. Consume del mismo presupuesto diario compartido con la producción real — `LenguajeInterface::completar()` ya verifica `PresupuestoLenguaje::disponible()` antes de cada llamada, así que `GeneradorVistaPrevia` no duplica ni rodea esa verificación.
- `Pluma\Admin\RestPeriodistas` (`GET /periodistas`, `GET /periodistas/{id}`, `GET /periodistas/plantillas`, `POST /periodistas/plantilla`, `POST /periodistas/{id}/{clonar,conducta,jubilar}`, `POST /periodistas/vista-previa`), capacidad `pluma_gestionar_periodistas` (la misma que ya protegía export/import del banco).
- `Pluma\Datos\RepositorioPiezas::metricasPorPeriodista()` — solo lectura, sin cambio de esquema.

**Decisiones tomadas al abrir esta porción (preguntadas explícitamente al propietario):**
1. **Alcance completo en una sola porción** (tarjetas + Estudio de Conducta + vista previa + acciones juntos) frente a partirla en dos — el propietario eligió la entrega completa, consistente con "cada pantalla llega 100% funcional antes de pasar a la siguiente".
2. **Tráfico omitido, comentarios también omitidos** de las métricas de tarjeta — el propietario eligió no mezclar señales de fuerza distinta (comentarios reales de WordPress sí existen, pero se dejaron fuera junto con tráfico para no inventar qué quiso decir el Libro con "métricas vivas" más allá de piezas/verticales).

**Nota de verificación en integración:** en el entorno de pruebas `wp-env` no hay ninguna llave de OpenRouter configurada, así que `POST /periodistas/vista-previa` sigue un camino determinista y sin red real: `ProveedorOpenRouter::completar()` lanza "sin credenciales" antes de cualquier llamada HTTP, y el test de integración verifica exactamente ese 503 — el camino de éxito con red real está cubierto por los tests Unit con un doble de `LenguajeInterface`, no por Integration.

## Porción 5 — Sala de Revisión visual (commit pendiente)

**Qué se agregó:**
- La bandeja de piezas RETENIDAS con **lectura limpia** (el HTML del último borrador, en un `<details>` desplegable) y **diagnóstico arriba** (motivos + el desglose completo de Compuertas — calidad/riesgo/originalidad), más la **cola de veto** del modo Copiloto con **cuenta regresiva en vivo** (se actualiza cada segundo hasta la hora límite de veto).
- Tres botones sobre RETENIDAS: **Aprobar**, **Devolver con nota** (con campo de texto), **Descartar** (con confirmación). Un botón **Vetar** sobre la cola de veto que llama al mismo endpoint `descartar` — `GestorSalaRevision::descartar()` ya expiraba la ranura de `pluma_cola_publicacion` cuando hacía falta desde la Etapa 3, sin cambios.
- `Pluma\Admin\RestSalaRevision` (existente desde la Etapa 3) **enriquecido**, no reescrito: `/revision/retenidas` y `/revision/veto` ahora incluyen `tendenciaTermino`, `periodista`, el `resultadoCompuertas` completo (antes solo `motivos`/`modoEfectivo`) y el `contenido` del último borrador — nuevas dependencias inyectadas (`RepositorioTendenciasInterface`, `RepositorioPeriodistasInterface`, `RepositorioBorradoresInterface`), sin cambio de esquema.

**Decisión de diseño relevante:** las notificaciones por Telegram/Slack con "enlaces de acción directa" (Cap. 10.2) NO se construyeron en esta porción — ya eran una decisión tomada en la Etapa 3 (documentada en el propio código de `NotificadorRevision`: "solo correo por ahora"), pero **nunca había quedado registrada formalmente como deuda** hasta ahora. Registrada como `PLUMA-E3-9` en `docs/deuda.md` al abrir esta porción. Motivo adicional para no abordarla junto con el rediseño visual: un enlace de acción directa clicable desde un correo/Telegram/Slack sin pasar por el login de wp-admin exige diseñar un mecanismo de autenticación de un solo uso que no exista todavía — no es una simple cuestión de formato de mensaje.

## Porción 6 — Sala de Máquinas completa (commit pendiente)

**Qué se agregó:**
- Bitácora del motor: últimas ejecuciones (inicio, duración calculada, lotes procesados, errores) — nuevo `RepositorioBitacora::obtenerRecientes()`.
- Coste de hoy contra el límite diario, con el límite editable desde la propia pantalla (`PresupuestoLenguaje::OPCION_LIMITE_DIARIO`).
- Estado de cada API conectada: OpenRouter (configurada o no + circuit breaker) y Google Trends (circuit breaker) — nuevos métodos públicos `circuitoAbierto()` en ambos proveedores, exponiendo en solo lectura un estado que ya existía internamente desde la Etapa 1/2.
- **La pantalla que por fin paga `PLUMA-E2-4`**: cargar, probar en vivo, cambiar y quitar la llave de OpenRouter. La llave nunca se devuelve en texto plano por ningún endpoint — solo un booleano "configurada" y, como mucho, sus últimos 4 caracteres.
- `Pluma\Admin\RestSalaMaquinas` (`GET /motor/bitacora`, `GET /motor/estado`, `POST`/`DELETE /motor/llave-openrouter`, `POST /motor/llave-openrouter/probar`, `POST /motor/presupuesto`), capacidad `pluma_configurar_motor`.
- `panel/src/PantallaSalud.tsx` renombrada a `PantallaSalaMaquinas.tsx` — mismo patrón que el rename de la porción 1, ahora que la pantalla es mucho más que "salud del entorno".

**Verificado contra documentación oficial de OpenRouter, no alucinado:** `GET https://openrouter.ai/api/v1/key` (créditos/límite restantes de una llave) es el endpoint real para la "prueba en vivo" — deliberadamente distinto de `LenguajeInterface::completar()`, que sí generaría coste real de una petición de redacción solo para validar una llave.

**Bug latente corregido de paso:** `RepositorioBitacora::obtenerUltima()`/`obtenerRecientes()` devolvían las fechas como la cadena cruda de MySQL (`Y-m-d H:i:s`, sin zona horaria) en vez de `DATE_ATOM` como el resto de repositorios del proyecto — sin efecto visible hasta ahora porque ningún frontend las parseaba como fecha (la Portada, que ya consume `obtenerUltima()` desde la porción 1, solo mira `.errores.length`). La nueva tabla de bitácora de esta porción sí necesita fechas parseables, así que se corrigió en la fuente para las dos.

**Honestidad de alcance (cero invención), tal como se acordó al abrir la porción:** "coste por pieza" no se construyó — `pluma_bitacora_motor` no atribuye gasto a una Pieza individual, solo hay agregado diario. "Reintentos" tampoco — no existe backoff automático todavía (`PLUMA-E3-7`, sigue abierta). Se muestra el gasto agregado real y los errores tal como se registraron, sin inventar una atribución o un mecanismo que no existen.

## Porción 7 — Estudio SEO y Taxonomía (commit pendiente)

**Qué se agregó:**
- **Auditoría de canibalización**: todos los grupos de Piezas ya PUBLICADAS que comparten la misma keyword principal, con el título real y el permalink de cada post — a diferencia de `AuditorCanibalizacion`/`existePiezaPublicadaConKeyword()` (Etapa 3), que solo responden "¿esta pieza colisiona con otra?" para UNA pieza en el momento de optimizarla, este endpoint lista el panorama agregado completo para el panel.
- **Salud taxonómica**: categorías/etiquetas en cuarentena (nombre, veces usada) y **propuestas de fusión** — pares de etiquetas NO en cuarentena con ≥85% de similitud que no comparten slug exacto (si lo compartieran, la reconciliación automática del Cap. 7.2 ya las habría fusionado). La pantalla es de **solo lectura**: muestra las propuestas pero no las ejecuta — fusionar de verdad implica reasignar términos en posts ya publicados, una operación de mayor riesgo que merece su propio diseño y no se decidió incluir en esta porción.
- `Pluma\Datos\RepositorioPiezas::obtenerCanibalizacion()` (nuevo, `GROUP BY keyword_principal HAVING COUNT(*) > 1` sobre piezas publicadas, solo lectura, sin cambio de esquema).
- `Pluma\Taxonomia\ReconciliadorVocabulario::similitud()` pasa de `private` a `public` (igual que `UMBRAL_SIMILITUD_PORCENTAJE`) para que el panel reutilice exactamente la misma comparación (`similar_text()` al 85%) que ya usa `reconciliar()` — cero duplicación de la función de similitud.
- `Pluma\Admin\RestEstudioSeo` (`GET /seo/canibalizacion`, `GET /seo/vocabulario`), capacidad `pluma_configurar_motor` — mismo criterio que la Sala de Máquinas: visibilidad técnica del motor SEO/Taxónomo, no aprobación editorial.

**Honestidad de alcance (cero invención), decidida antes de abrir esta porción:** "estado de indexación por pieza" y "keywords en el umbral 5-15" (posiciones 5–15, con botón "crear pieza de refuerzo") del Cap. 10.2 quedan fuera — dependen de Google Search Console, que no existe todavía (`PLUMA-E3-5`, Etapa 5), mismo criterio ya aplicado a "resultados de ayer" (Portada) y "tráfico medio" (Banco de Periodistas).

## Pendiente dentro de esta Etapa

Pantallas del Cap. 10.2 sin construir todavía: el **onboarding de 5 actos** (Cap. 10.3) — la última pieza de esta Etapa.

Deuda de etapas anteriores explícitamente asignada a la Etapa 4:

| Ticket | Deuda | Nota |
|---|---|---|
| ~~PLUMA-E2-4~~ | ~~Sin pantalla/endpoint para cargar la llave de OpenRouter~~ | **Pagada en la porción 6** (Sala de Máquinas completa) |
| PLUMA-E3-1 | Sitemap de noticias con ping de indexación | Sin priorizar todavía |
| PLUMA-E3-6 | Modo pausa / modo respeto (toggle en el panel) | Sin priorizar todavía — candidata natural a la porción de Sala de Máquinas ya que esta ya tiene la configuración técnica del motor, pero no se abordó en la porción 6 para no ampliar más su alcance |
| PLUMA-E3-8 | Detección activa de WP-Cron real + guía de instalación por hosting | Portada ya muestra si el cron está configurado (heredado de Etapa 0), pero falta la guía activa |
| PLUMA-E3-4 (ver `docs/etapa-3-capa-competitiva.md`) | JSON-LD nunca se emite en el frontend — verificado como no pagado pese a que `docs/deuda.md` lo daba por hecho en H3 de la Etapa 3 | Descubierto durante la redacción de este documento, no durante código de la Etapa 4 |
| PLUMA-E3-9 | Notificaciones por Telegram/Slack con enlaces de acción directa — solo hay correo | Registrada al abrir la porción 5 (Sala de Revisión); exige además diseñar autenticación de un solo uso para los enlaces de acción, no solo el envío del mensaje |

## A tener en cuenta para otras fases

- **`react`, `react-dom` y `diff` son ahora dependencias de producción reales** del bundle del panel (`build/panel/`) — cualquier auditoría de licencias (`LICENSES-THIRD-PARTY.md`) debe seguir actualizándose cada vez que el panel incorpore una librería nueva, no solo el lado PHP.
- **El shell (`Aplicacion.tsx`) solo enlaza pantallas que existen de verdad** — cada porción nueva añade su propia entrada de navegación al terminar, nunca antes (cero enlaces muertos). Quien construya la próxima porción debe seguir este mismo patrón.
- **`PresupuestoLenguaje` sigue siendo un único pool diario compartido** (Etapa 2), y desde la porción 4 la vista previa en vivo del Estudio de Conducta también consume de ahí — introducir un pool separado para vista previa sería una regresión sobre la decisión ya tomada (2026-07-23).
- **El renombre de `PantallaSalud` a `PantallaPanel`** (porción 1) es un punto de fricción si alguna documentación o memoria de sesiones anteriores todavía referencia el nombre viejo — verificado que no quedan referencias en `src/` al cierre de la porción 3.
- **Cualquier endpoint REST nuevo que agregue una pantalla del panel a wp-env debe correr también Playwright E2E localmente antes de cerrar la porción** — la porción 1 renombró el slug/id del shell (`pluma-engine-salud`→`pluma-engine-panel`) y esto rompió el smoke E2E de la Etapa 0 en CI (run [30025921402](https://github.com/jhonnfrank1995/PLUMA/actions/runs/30025921402)) porque Playwright no se había corrido en ninguna de las tres primeras porciones, solo PHPUnit/PHPCS/PHPStan/Vitest/Integration wp-env. Corregido en `c45494b`; a partir de la porción 4 el Delivery Guardian de esta etapa incluye `npx playwright test` antes de cerrar.
- **`PropositoLenguaje::VistaPrevia` es un propósito nuevo, deliberadamente NO premium** — cualquier ajuste futuro al enrutamiento de modelos (`EnrutadorModelos`) debe mantenerlo en la rama económica; moverlo a premium encarecería cada movimiento de un dial en el Estudio de Conducta.
- **`GeneradorVistaPrevia` construye un `Periodista`/`ConductaVersion` sintéticos en memoria** (id de versión `0`, nunca persistido) solo para reutilizar `CompiladorDirectrices::compilar()` — si `CompiladorDirectrices` cambia su firma o dependencias en el futuro, este generador debe actualizarse en paralelo.
- **`RestSalaRevision` demuestra el patrón correcto para "enriquecer sin reescribir"**: la porción 5 le agregó tres dependencias nuevas y una función privada compartida (`piezaComoArray()`) reutilizada entre `retenidas()` y `colaDeVeto()`, sin tocar `GestorSalaRevision` ni el grafo del Transicionador — cuando una pantalla ya tiene REST funcional de una etapa anterior, la porción visual casi siempre debe ser "enriquecer la serialización", no "reescribir el backend".
- **El registro DI del Contenedor no memoiza por interfaz**: `ProveedorOpenRouter`/`ProveedorGoogleTrends` ya estaban registrados detrás de `LenguajeInterface::class`/`ProveedorTendenciasInterface::class` respectivamente; la porción 6 tuvo que añadir un registro ADICIONAL del tipo concreto (`ProveedorOpenRouter::class`, `ProveedorGoogleTrends::class`) para poder inyectar métodos propios (`circuitoAbierto()`, `probarLlave()`) que no pertenecen al contrato de la interfaz. Cualquier clase futura que necesite un método propio de una implementación concreta debe seguir este mismo patrón de doble registro, sin tocar el contrato de la interfaz.
- **Toda fecha que un repositorio expone a un endpoint REST debe ser `DATE_ATOM`**, nunca la cadena cruda de MySQL — la porción 6 encontró y corrigió un caso donde esto no se cumplía (`RepositorioBitacora`) precisamente porque nadie había necesitado parsear esa fecha como `Date` hasta ahora. Vale la pena revisar los demás repositorios si alguna porción futura empieza a mostrar fechas sin parsearlas correctamente.
- **Un método `private` de una clase de dominio puede pasar a `public` sin duplicar lógica cuando el panel necesita EXACTAMENTE la misma comparación** (porción 7, `ReconciliadorVocabulario::similitud()`) — más seguro que copiar la llamada a `similar_text()` en el controlador REST, porque un cambio futuro al umbral o al algoritmo de similitud se propaga automáticamente a ambos consumidores. Antes de exponer un método así, verificar que no tenga efectos secundarios ni dependa de estado interno que no deba ser público.
- **Una pantalla del panel no tiene por qué incluir acciones de escritura solo porque las otras las tienen**: la porción 7 (Estudio SEO y Taxonomía) es deliberadamente de solo lectura — "fusionar etiquetas de verdad" tocaría posts ya publicados y merece su propio diseño de riesgo/autorización, no una casilla más en esta porción. Cuando el Libro describe una pantalla en términos de "auditoría"/"salud" sin nombrar acciones concretas, no inventar una acción de escritura solo para uniformar el patrón de las porciones anteriores.

## Evidencia de gates

| Porción | Unit | Invariantes | Integration (wp-env real) | Vitest | E2E | PHPCS / PHPStan L8 | Push + CI |
|---|---|---|---|---|---|---|---|
| 1 — Portada | — | — | 46/46 | 19/19 | no corrido | limpio | ✅ verde (con fix posterior de E2E) |
| 2 — Sala de Tendencias | 299/299 | 21/21 | 52/52 | 24/24 | no corrido | limpio | ✅ verde |
| 3 — Mesa Editorial | 301/301 | 21/21 | 60/60 | 32/32 | no corrido | limpio | ✅ verde |
| 4 — Banco de Periodistas | 304/304 | 21/21 | 70/70 | 42/42 | 2/2 | limpio | commiteado, sin push todavía |
| 5 — Sala de Revisión | 304/304 | 21/21 | 71/71 | 48/48 | 2/2 | limpio | commiteado, sin push todavía |
| 6 — Sala de Máquinas | 311/311 | 21/21 | 80/80 | 51/51 | 2/2 | limpio | commiteado, sin push todavía |
| 7 — Estudio SEO y Taxonomía | 312/312 | 21/21 | 84/84 | 55/55 | 2/2 | limpio | commiteado, sin push todavía |

Build de producción del panel verificado (`npm run build`) al cierre de cada porción. Sin llave de API filtrada en ningún commit (verificado explícitamente antes de cada uno).
