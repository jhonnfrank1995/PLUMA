# Licencias de Terceros — PLUMA Engine

Este documento lista las dependencias de **producción** (las que viajan dentro
del ZIP de distribución, prefijadas vía PHP-Scoper cuando corresponda). Las
herramientas de desarrollo (PHPUnit, PHPCS, PHPStan, Vite, Vitest, Playwright,
`@wordpress/env`, etc.) **no se distribuyen** con el plugin — viven en
`require-dev` / `devDependencies` y quedan fuera del paquete final.

Se regenera con `composer licenses --no-dev` (PHP) y `npm ls --omit=dev`
(JS) en cada release, como parte del checklist del sub-agente RELEASE
(AGENTS.md).

## Estado — Etapa 0 (Cimientos)

A la fecha de este commit, PLUMA Engine **no tiene ninguna dependencia de
producción PHP ni JS**: el Kernel, el ciclo de vida y la pantalla de Salud
están escritos exclusivamente sobre la API de WordPress y la librería
estándar de PHP/React. `vendor/` en el ZIP distribuido no contiene paquetes
de terceros.

Esta tabla se completará en cuanto una Etapa futura (Investigador, Proveedor
de Lenguaje, Motor SEO, etc.) introduzca una dependencia real de producción.

| Paquete | Versión | Licencia | Uso |
|---|---|---|---|
| — | — | — | (ninguna dependencia de producción todavía) |
