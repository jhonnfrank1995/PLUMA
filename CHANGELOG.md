# CHANGELOG — PLUMA Engine
Formato: [Keep a Changelog] · Versionado: SemVer. Cada entrada se escribe para el cliente, no para el desarrollador.

## [Unreleased]
### Añadido
- Capa de gobernanza completa del proyecto (CLAUDE.md, GOVERNANCE.md, AGENTS.md, SKILLS-STACK.md, ECOSYSTEM.md, PLAN-MAESTRO.md, skills `pl-*` y `lg-*` en `.claude/skills/`).
- Libro de Arquitectura v1.0 como fuente de producto (`docs/`).
- **Etapa 0 — Cimientos**: esqueleto instalable del plugin.
  - Kernel: `Contenedor` (DI propio), `Activador`/`Desactivador`/`Desinstalador` (ciclo de vida completo, multisitio), `Capacidades` propias (`pluma_gestionar_periodistas`, `pluma_aprobar_piezas`, `pluma_configurar_motor`), `RelojInterface`/`RelojSistema`, `DetectorEntorno`.
  - `Pluma\Datos\Migrador`: infraestructura de `pluma_db_version` con `dbDelta` idempotente (sin tablas todavía — nacen en la Etapa 1).
  - `Pluma\Admin\PantallaSalud`: primera pantalla real del panel (Sala de Máquinas — Salud del sistema), React + Vite, capacidad `pluma_configurar_motor`, i18n completa.
  - Toolchain de calidad: PHPCS (WordPress-Extra + Security), PHPStan nivel 8 (+ stubs de WordPress), PHPUnit + Brain\Monkey (28 tests Unit), PHPUnit de integración vía `wp-env` (`CicloDeVidaTest`), Vitest (4 tests), PHP-Scoper.
  - Empaquetado reproducible (`bin/build-zip`) y CI en GitHub Actions (calidad PHP, calidad JS, integración wp-env, empaquetado + smoke de instalación/activación/desactivación/desinstalación + Playwright E2E).
  - `LICENSES-THIRD-PARTY.md` (sin dependencias de producción todavía).
  - **Cerrada el 2026-07-22**: CI en verde en GitHub Actions (run [29968501162](https://github.com/jhonnfrank1995/PLUMA/actions/runs/29968501162)) — los 4 jobs (calidad PHP, calidad JS, integración wp-env, empaquetado) en `success`. Criterio de salida del PLAN-MAESTRO cumplido: el ZIP construido reproduciblemente se instala, activa, desactiva y desinstala limpio sobre un WordPress real, conservando los datos del cliente por defecto tal como exige GOVERNANCE §5.4.
- **Etapa 1 — El esqueleto que camina**: una tendencia real recorre el pipeline de punta a punta hasta un borrador WordPress trazable.
  - `Pluma\Pipeline`: `EstadoPieza` (grafo completo, `references/estados.md`), `Pieza` (DTO inmutable), `Transicionador` (única puerta de transición, candado por-Pieza optimista, auditoría, eventos `pluma/pieza_*`), `Orquestador` (detecta, avanza el pipeline por lotes, presupuesto de tiempo con corte limpio, candado global).
  - `Pluma\Datos\CandadoGlobal`: candado del orquestador vía `GET_LOCK()`/`RELEASE_LOCK()` de MySQL — sobrevive a que el proceso PHP muera a mitad de lote sin depender de que un `finally` llegue a ejecutarse.
  - Esquema nuevo (`Pluma\Datos\Esquema`, versión `0.2.0`): tablas `pluma_tendencias`, `pluma_piezas`, `pluma_fuentes`, `pluma_bitacora_motor`, `pluma_auditoria`.
  - `Pluma\Proveedores\ProveedorGoogleTrends`: primer Sensor de Radar, sobre el feed público de tendencias diarias de Google (sin credenciales), con circuit breaker por fallos consecutivos y validación SSRF (`ValidadorUrl`).
  - `Pluma\Sensores\SensorGoogleTrends` + `PuntuacionOportunidad`: puntuación de Velocidad + Afinidad (Hueco competitivo y Vida útil quedan en `docs/deuda.md`, dependen del Motor SEO).
  - `Pluma\Investigacion\InvestigadorMecanico` + `Pluma\Redaccion\RedactorMecanico`: expediente y borrador construidos mecánicamente desde los hechos reales del Sensor — sin periodista sintético (llega en la Etapa 2) ni llamadas a un proveedor de lenguaje, cero contenido inventado.
  - `Pluma\Publicacion\CreadorBorrador`: único punto con `wp_insert_post`, siempre en modo Piloto (`post_status=draft`).
  - `Pluma\Admin\RestOrquestador`: endpoint `pluma/v1/motor/tick` (GET/POST) con token rotable (`X-Pluma-Token`), rate limit y presupuesto de tiempo configurable — el punto de entrada del cron real del servidor.
  - `docs/hooks.md`: primer registro de eventos públicos del plugin.
  - Tests: 26 nuevos Unit (Transicionador, Orquestador, Sensor/Proveedor con fixture real del feed, Puntuación) + Integración contra WordPress real (doble ejecución simultánea, muerte a mitad de lote, endpoint sin token/con token inválido/válido, repositorios `pluma_piezas`/`pluma_tendencias`).
