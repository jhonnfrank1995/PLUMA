# Licencias de Terceros — PLUMA Engine

Este documento lista las dependencias de **producción** (las que viajan dentro
del ZIP de distribución, prefijadas vía PHP-Scoper cuando corresponda). Las
herramientas de desarrollo (PHPUnit, PHPCS, PHPStan, Vite, Vitest, Playwright,
`@wordpress/env`, etc.) **no se distribuyen** con el plugin — viven en
`require-dev` / `devDependencies` y quedan fuera del paquete final.

Se regenera con `composer licenses --no-dev` (PHP) y `npm ls --omit=dev`
(JS) en cada release, como parte del checklist del sub-agente RELEASE
(AGENTS.md).

## Estado — Etapa 4 (La experiencia premium)

PLUMA Engine sigue sin dependencias de producción PHP: `vendor/` en el ZIP
distribuido no contiene paquetes de terceros para la lógica del plugin.

El panel React (`build/panel/`, el único bundle JS que se distribuye — se
carga solo en la pantalla propia del plugin, GOVERNANCE §pl-wp-core) sí
empaqueta una dependencia real desde la Mesa Editorial (Cap. 10.2, vista de
diff entre ciclos de borrador):

| Paquete | Versión | Licencia | Uso |
|---|---|---|---|
| `react` | ^18.3.1 | MIT | Motor de la interfaz del panel. |
| `react-dom` | ^18.3.1 | MIT | Renderizado de React sobre el DOM del panel. |
| `diff` (jsdiff) | ^9.0.0 | BSD-3-Clause | Diff línea a línea entre ciclos de un borrador en la Mesa Editorial. |
