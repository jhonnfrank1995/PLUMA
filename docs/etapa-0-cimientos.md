# Etapa 0 — Cimientos

**Estado:** Cerrada el 2026-07-22 · CI en verde en GitHub Actions (run [29968501162](https://github.com/jhonnfrank1995/PLUMA/actions/runs/29968501162)).

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Repo, gobernanza activa, esqueleto de plugin instalable, CI con los gates, `wp-env`.
> **Criterio de salida**: el ZIP vacío instala/activa/desinstala limpio; `/qa` en verde en CI.

## Qué se agregó

- **Gobernanza del proyecto**: `CLAUDE.md`, `GOVERNANCE.md`, `AGENTS.md`, `SKILLS-STACK.md`, `ECOSYSTEM.md`, `PLAN-MAESTRO.md`, y las skills propias `pl-*`/`lg-*` en `.claude/skills/`.
- **Libro de Arquitectura v1.0** (`docs/PLUMA_Engine_Libro_de_Arquitectura.md`) como fuente de producto — capítulos 1–14, ley de producto por encima del código.
- **`Pluma\Kernel`**: `Contenedor` (contenedor de inyección de dependencias propio, sin librería externa), `Activador`/`Desactivador`/`Desinstalador` (ciclo de vida completo, con soporte multisitio), `Capacidades` propias (`pluma_gestionar_periodistas`, `pluma_aprobar_piezas`, `pluma_configurar_motor` — nunca colgadas de `manage_options`), `RelojInterface`/`RelojSistema` (tiempo inyectable, sin `time()` directo en `src/`), `DetectorEntorno`.
- **`Pluma\Datos\Migrador`**: infraestructura de versión de esquema (`pluma_db_version`) con `dbDelta` idempotente. Sin tablas propias todavía — nacen en la Etapa 1.
- **`Pluma\Admin\PantallaSalud`** (renombrada a `PantallaPanel` en la Etapa 4): primera pantalla real del panel — "Sala de Máquinas — Salud del sistema", React + Vite, protegida con la capacidad `pluma_configurar_motor`, i18n completa desde el primer commit.
- **Toolchain de calidad**: PHPCS (`WordPress-Extra` + reglas de seguridad), PHPStan nivel 8 (con stubs de WordPress), PHPUnit + Brain\Monkey (28 tests Unit), PHPUnit de integración vía `wp-env` (`CicloDeVidaTest`), Vitest (4 tests), PHP-Scoper para el scoping de dependencias.
- **Empaquetado reproducible** (`bin/build-zip`) y **CI en GitHub Actions**: calidad PHP, calidad JS, integración `wp-env`, empaquetado + smoke test de instalación/activación/desactivación/desinstalación + Playwright E2E.
- **`LICENSES-THIRD-PARTY.md`**: creado sin dependencias de producción todavía (ni PHP ni JS) — el primer paquete real de producción llega hasta la Etapa 4 porción 3 (`diff`/jsdiff).

## Qué se corrigió / decisiones no triviales

Esta Etapa se construyó antes del inicio de esta sesión de trabajo, por lo que no hay aquí un historial de depuración detallado (bugs encontrados y corregidos durante la construcción) — solo lo que consta explícitamente en `CHANGELOG.md`. La decisión de diseño más relevante registrada es estructural, no correctiva: la desinstalación respeta por defecto **conservar los datos del cliente** (GOVERNANCE §5.4), verificado en el propio smoke test de CI como parte del criterio de salida.

## Deuda técnica de esta etapa

| Ticket | Deuda | Pago asignado |
|---|---|---|
| PLUMA-E0-1 | `tests/Integration/*` no cubre activación de red completa en multisitio real (solo cubierto vía dobles en `ActivadorTest` Unit); requiere `.wp-env.json` en modo multisitio, no configurado todavía | Etapa 3 (cuando Compuertas/Taxónomo añadan superficie real que justifique el entorno multisitio) — **sigue sin pagarse al cierre de la Etapa 3**; revisar si se traslada a una etapa futura |

## A tener en cuenta para otras fases

- **Toda tabla `pluma_*` nueva nace en `Pluma\Datos\Esquema`** con `dbDelta`, nunca con `ALTER TABLE` escrito a mano — este patrón se mantuvo sin excepción hasta la Etapa 4 (esquema `0.9.0` al momento de escribir este documento).
- **Las tres capacidades propias fijadas aquí** (`pluma_gestionar_periodistas`, `pluma_aprobar_piezas`, `pluma_configurar_motor`) son las únicas que existen todavía en Etapa 4 — cada pantalla nueva del panel reutiliza una de estas tres en vez de crear capacidades nuevas sin necesidad real.
- **`PantallaSalud` fue la única pantalla del panel hasta la Etapa 4**: en la porción 1 de la Etapa 4 se renombró a `PantallaPanel` (la página única que arranca el shell React) y su contenido de salud del sistema pasó a vivir dentro del shell como la pantalla "Sala de Máquinas". Quien busque `PantallaSalud.php` en el código a partir de la Etapa 4 no la encontrará con ese nombre.
- **`RelojInterface`/`RelojSistema` y el patrón de tiempo inyectable** establecido aquí (pl-testing: "cero `time()` directo en `src/`") se exigió sin excepción en absolutamente todo el código posterior.

## Evidencia de gates al cierre

28 tests Unit + integración `wp-env` (`CicloDeVidaTest`) + 4 tests Vitest — los 4 jobs de CI (calidad PHP, calidad JS, integración wp-env, empaquetado) en `success`.
