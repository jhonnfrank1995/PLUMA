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
