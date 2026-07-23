# Etapa 1 — El esqueleto que camina

**Estado:** Cerrada el 2026-07-23 · CI en verde en GitHub Actions (run [29973288960](https://github.com/jhonnfrank1995/PLUMA/actions/runs/29973288960)).

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Kernel+DI, tablas y máquina de estados de Pieza, motor cron con candado y bitácora, un Sensor (Trends), Piloto.
> **Criterio de salida**: una tendencia real termina en borrador trazable de punta a punta; doble ejecución y muerte a mitad de lote probadas.

## Qué se agregó

- **`Pluma\Pipeline`**: `EstadoPieza` (el grafo completo de estados, documentado en `references/estados.md`), `Pieza` (DTO inmutable), `Transicionador` (única puerta de transición de estado — candado por-Pieza optimista, auditoría, eventos `pluma/pieza_*` vía `do_action`), `Orquestador` (detecta tendencias, avanza el pipeline por lotes con presupuesto de tiempo y corte limpio, candado global).
- **`Pluma\Datos\CandadoGlobal`**: candado del Orquestador implementado sobre `GET_LOCK()`/`RELEASE_LOCK()` de MySQL — sobrevive a que el proceso PHP muera a mitad de lote, sin depender de que un `finally` llegue a ejecutarse.
- **Esquema nuevo** (`Pluma\Datos\Esquema`, versión `0.2.0`): tablas `pluma_tendencias`, `pluma_piezas`, `pluma_fuentes`, `pluma_bitacora_motor`, `pluma_auditoria`.
- **`Pluma\Proveedores\ProveedorGoogleTrends`**: primer Sensor del Radar, sobre el feed público de tendencias diarias de Google (sin credenciales), con circuit breaker por fallos consecutivos y validación SSRF (`ValidadorUrl`).
- **`Pluma\Sensores\SensorGoogleTrends` + `PuntuacionOportunidad`**: puntuación de Velocidad + Afinidad (Hueco competitivo y Vida útil quedan explícitamente en deuda — dependen del Motor SEO y de la memoria de audiencia, ninguno de los dos existía todavía).
- **`Pluma\Investigacion\InvestigadorMecanico` + `Pluma\Redaccion\RedactorMecanico`**: expediente y borrador construidos mecánicamente a partir de los hechos reales del Sensor — sin periodista sintético (llega en la Etapa 2) ni llamadas a un proveedor de lenguaje. Cero contenido inventado, por diseño.
- **`Pluma\Publicacion\CreadorBorrador`**: único punto del plugin con `wp_insert_post`, siempre en modo Piloto (`post_status=draft`) en esta etapa.
- **`Pluma\Admin\RestOrquestador`**: endpoint `pluma/v1/motor/tick` (GET/POST) con token rotable (`X-Pluma-Token`), rate limit y presupuesto de tiempo configurable — el punto de entrada real del cron del servidor (WP-Cron nunca se usa para el motor).
- **`docs/hooks.md`**: primer registro de los eventos públicos del plugin.
- **Tests**: 26 nuevos Unit (Transicionador, Orquestador, Sensor/Proveedor con fixture real del feed, Puntuación) + Integración contra WordPress real (doble ejecución simultánea del Orquestador, muerte a mitad de lote, endpoint sin token / con token inválido / con token válido, repositorios `pluma_piezas`/`pluma_tendencias`).

## Qué se corrigió / decisiones no triviales

Igual que la Etapa 0, esta etapa se construyó antes del inicio de esta sesión de trabajo — no hay historial de depuración línea a línea disponible. La decisión de diseño explícitamente registrada y más relevante para etapas futuras es de **alcance, no de corrección**: el criterio de salida de la Etapa 1 pedía "un borrador trazable, aunque sea rudimentario", y el propietario aprobó que el redactor de esta etapa fuera puramente mecánico (sin periodista, sin IA) — la Sala de Redacción completa quedó deliberadamente diferida a la Etapa 2. Esta decisión está documentada en `docs/skills-descubiertas.md` ("Apertura de Etapa 1") como un hueco resuelto explícitamente con el propietario antes de codificar.

## Deuda técnica de esta etapa

| Ticket | Deuda | Pago asignado |
|---|---|---|
| PLUMA-E1-1 | `PuntuacionOportunidad` solo calcula Velocidad (35%) y Afinidad (30%), normalizados sobre el 65% disponible — faltan Hueco competitivo (20%, necesita datos de SERP) y Vida útil (15%, necesita clasificación de perecibilidad) | Etapa 3 (Motor SEO/Search Console) y Etapa 5 (memoria de audiencia) — **sigue abierta**: la Etapa 3 no llegó a cerrar esta puerta (el Motor SEO no expone todavía datos de SERP al Radar) y la Sala de Tendencias de la Etapa 4 (porción 2) declara explícitamente en pantalla que el desglose es parcial por esta misma razón |
| PLUMA-E1-2 | `RepositorioTendencias`/`SensorGoogleTrends` deduplican por término normalizado exacto, no por huella semántica (Cap. 3.4: detectar la misma historia con distinto titular, o una historia que evoluciona) | Etapa 2+ (cuando el Investigador multifuente dé señal suficiente para triangular equivalencia semántica) — **sigue abierta** al cierre de la Etapa 3 |
| PLUMA-E1-3 | `InvestigadorMecanico` no implementa el protocolo de 5 pasos del Cap. 4 (fuente primaria, 4-8 coberturas secundarias, triangulación, contexto, hueco); usa directamente los artículos que el propio feed de Google Trends agrega, con nivel `Atribuido` único | Etapa 3 — **sigue abierta**: la Etapa 3 priorizó Compuertas/SEO/Taxónomo/Orquestador sobre el Investigador multifuente completo; revisar si se traslada a Etapa 4/5 |

## A tener en cuenta para otras fases

- **El grafo de `EstadoPieza` y el `Transicionador` fijados aquí son la única puerta de transición de estado en todo el proyecto** — la Etapa 3 (Sala de Revisión) y la Etapa 4 (Mesa Editorial, Sala de Tendencias) solo añadieron aristas nuevas al mismo grafo (p. ej. `retenida → {optimizada, aprobada, descartada}`), nunca un mecanismo paralelo.
- **El candado global sobre `GET_LOCK()`/`RELEASE_LOCK()`** es la base de la "escasez honesta" y de la ejecución única del Orquestador en toda etapa posterior — cualquier trabajo futuro que toque el ciclo del motor debe pasar por él, no crear un candado paralelo.
- **`PuntuacionOportunidad` sigue incompleta (PLUMA-E1-1) y ya es visible al usuario** desde que la Sala de Tendencias (Etapa 4) expone el desglose en pantalla — quien cierre esta deuda debe actualizar tanto el cálculo como el texto que hoy declara la limitación en `PantallaPanel::textosTendencias()`.
- **`RedactorMecanico` no desapareció en la Etapa 2**: sigue siendo el *fallback* real cuando no hay presupuesto/credenciales de proveedor de lenguaje (ver `RedactorConFallbackMecanico`, deuda PLUMA-E2-1) — el código de esta etapa sigue vivo y en uso, no es código muerto.

## Evidencia de gates al cierre

26 tests Unit nuevos + integración contra WordPress real (doble ejecución simultánea, muerte a mitad de lote, endpoint del cron, repositorios) — los 4 jobs de CI en `success`.
