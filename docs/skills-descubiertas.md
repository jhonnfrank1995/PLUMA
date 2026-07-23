# Skills descubiertas — mapa de aplicabilidad (SKILLS-STACK §2)
Fecha de inventario: 2026-07-22 · Fuente: ecosistema del propietario (recursos de proyectos anteriores)

| Skill del ecosistema | Veredicto | Destino en PLUMA |
|---|---|---|
| lg-labs + 13 skills LG | APLICA íntegro | Capa de razonamiento de todo el proyecto (SKILLS-STACK §1) |
| exception-handling-and-logging | APLICA | Pluma\Proveedores + bitácora del motor |
| http-client-resilience | APLICA | Pluma\Proveedores (timeouts, backoff, circuit breaker) |
| ui-ux-pro-max | APLICA | Panel completo (tokens, tipografía, estados) |
| frontend-design | APLICA | Dirección estética del panel |
| kpi-dashboard-design | APLICA | Portada / dashboard del cap. 10 |
| tailwind-design-system | ADAPTAR | Sistema de tokens del panel (stack React del panel), con cautela por conflicto con CSS de wp-admin |
| web-design-guidelines | APLICA | Frontend público (bloque del editor) |
| spatie-security | ADAPTAR | Los principios sí; la implementación se traduce a capacidades WP |
| laravel-specialist / filament-pro / pest-testing / queues-and-horizon | NO APLICA directo | Stack Laravel; sus patrones (colas, testing por contratos) inspiran pl-pipeline y pl-testing |
| ri-processors / ri-filament / ri-testing / ri-ai-providers | NO APLICA (proyecto RoyalImage) | Su ESTRUCTURA es la plantilla de los skills pl-* de este repo |

## Ampliación — auditoría de `~/.claude/skills/` (2026-07-22, protocolo §2 completo)

Inventario ejecutado contra el directorio global de skills del agente (cientos de carpetas). Se excluyó explícitamente todo lo ofensivo/pentest (no aplica: PLUMA es un producto defensivo, no una auditoría ofensiva) y stacks ajenos (Laravel/Rails/mobile/blockchain/juegos/CRM de terceros).

| Skill | Veredicto | Destino en PLUMA |
|---|---|---|
| wordpress-plugin-development | ADAPTAR | Núcleo del plugin (hooks, admin, REST, capacidades); ignorar material de WP futuro no soportado, centrarse en PSR-4/`Pluma\*`, dbDelta, nonces |
| wordpress | ADAPTAR | Paraguas general; usar solo la porción plugin/seguridad/performance (no es theme ni WooCommerce) |
| llm-structured-output | APLICA | `Pluma\Proveedores` / `LenguajeInterface`: salidas estructuradas (JSON Schema) de los periodistas sintéticos |
| api-security-best-practices | ADAPTAR | REST controllers del panel: nonces + `permission_callback` + capacidades propias en vez de JWT/OAuth genérico |
| backend-security-coder | ADAPTAR | Sanitización/escape en todo el backend: `$wpdb->prepare`, `sanitize_*`, `esc_*` en vez de ejemplos genéricos |
| seo-schema / schema-markup / schema-markup-generator / seo-aeo-schema-generator | APLICA | Motor SEO: JSON-LD NewsArticle/OpinionNewsArticle |
| seo-sitemap | APLICA | Motor SEO: sitemap Google News (`news:news`, ventana 48h) |
| seo-technical | APLICA | Motor SEO: crawlability, structured data, convivencia con Yoast/Rank Math |
| seo-fundamentals | APLICA | E-E-A-T y Core Web Vitals → compuerta de calidad/originalidad |
| seo (umbrella) | APLICA | Auditoría end-to-end del Motor SEO |
| ai-seo / seo-geo | APLICA (adaptar a noticias) | Motor SEO: visibilidad en AI Overviews/ChatGPT/Perplexity |
| programmatic-seo | ADAPTAR | Patrón de páginas a escala análogo a publicación autónoma; usar su checklist anti-thin-content en compuerta de originalidad |
| seo-content-writer | ADAPTAR | `Pluma\Redaccion`; combinar con anti-alucinación y trazabilidad de fuentes de Investigación |
| wordpress-centric-high-seo-optimized-blogwriting-skill | ADAPTAR | Referencia de formato; sustituir "metadata Yoast" por Motor SEO/compuertas propias |
| avoid-ai-writing | APLICA | Compuerta de calidad editorial: que los periodistas sintéticos no suenen genéricos |
| professional-proofreader | ADAPTAR | Paso de post-edición automatizado dentro de la compuerta de calidad (no interactivo) |
| i18n-localization | APLICA | i18n completo del plugin (textdomain, .pot/.po) |
| accessibility-compliance-accessibility-audit / wcag-audit-patterns / ui-a11y | APLICA (ui-a11y el más accionable) | Panel admin React, cumplimiento WCAG |
| screen-reader-testing | ADAPTAR | Panel admin, adaptado a componentes React de PLUMA |
| react-best-practices | APLICA | Panel admin: performance/rendering |
| frontend-dev-guidelines | ADAPTAR | Adaptar fetching a `apiFetch`/REST WP en vez de Next.js/RSC |
| radix-ui-design-system | ADAPTAR | Solo si no colisiona con `@wordpress/components` |
| tailwind-design-system | ADAPTAR | Ver nota de cautela arriba |
| design-taste-frontend / ui-ux-designer | ADAPTAR | Estados de componentes/tipografía del panel; ignorar sesgos anti-wp-admin |
| backend-dev-guidelines / backend-architect | ADAPTAR | Traducir patrones (Node/Express) a PHP/WP: repositorios sobre `$wpdb`, servicios namespaced; boundaries para Sensores/Investigación/Proveedores |
| api-design-principles / api-patterns | ADAPTAR | REST controllers: versionado/paginación/envelopes de error, auth vía nonces WP |
| test-driven-development / tdd-workflow | APLICA | Ciclo red-green-refactor sobre PHPUnit |
| unit-testing-test-generate | APLICA | PHPUnit + Brain\Monkey |
| javascript-testing-patterns | ADAPTAR | Vitest del panel admin |
| e2e-testing / e2e-testing-patterns / playwright-skill | APLICA | Playwright E2E |
| secrets-management | ADAPTAR | Credenciales de proveedores IA: `wp_options` cifrado / constantes en `wp-config.php`, no Vault/AWS Secrets Manager |
| llm-app-patterns | ADAPTAR | Pipeline retrieval→generate para `Pluma\Investigacion` (extraer solo el patrón, no el stack Dify) |
| llm-evaluation | ADAPTAR | Métricas para la compuerta de calidad de los periodistas sintéticos |
| llm-prompt-optimizer / prompt-engineering / prompt-engineering-patterns | ADAPTAR | Diseño de prompts vía `LenguajeInterface`; NO cubren la neutralización anti-inyección obligatoria (eso es implementación propia de `Pluma\Proveedores`) |
| prompt-caching | ADAPTAR | Control de coste en publicación autónoma con presupuesto |
| workflow-orchestration-patterns / saga-orchestration | ADAPTAR | Traducir a cron propio single-node con candado global, no a motor de workflows externo (Temporal) |
| php-pro | ADAPTAR | PHP moderno junto con PHPStan L8 y `strict_types`, sin el enfoque framework-agnostic genérico |

**Excluidos explícitamente (NO APLICA):** `broken-authentication`, `security-audit`, `wordpress-penetration-testing` y demás skills de pentest ofensivo (fuera de alcance: PLUMA es defensivo); `cost-optimization` (coste de nube, no de tokens/API — para eso `prompt-caching`/`llm-ops`); `content-strategy`/`content-creator`/`copywriting` (marketing/conversión genérico, no periodismo con anti-alucinación — uso marginal como referencia); `wordpress-theme-development`, `wordpress-woocommerce-development` (PLUMA es plugin, no theme ni tienda).

Huecos identificados (investigar en fuentes oficiales antes de codificar): WordPress REST/dbDelta/i18n avanzado (→ `pl-wp-core` creado), schema.org NewsArticle/OpinionNewsArticle, protocolo de sitemap de Google News, API de Search Console, neutralización anti-inyección de prompts en `LenguajeInterface` (sin skill de ecosistema que lo cubra — implementación propia documentada en `pl-proveedor-ia`). Revisar este mapa al abrir cada Etapa (`/nueva-etapa`).

## Apertura de Etapa 0 — revisión del mapa (2026-07-22)

Inventario re-ejecutado sobre `.claude/skills/` (ubicación consolidada: los 6 `pl-*` y los 14 LG viven ahí desde 2026-07-22; las carpetas `skills/` y `skills-lg/` ya no existen). Los 20 skills son descubiertos y cargables por el agente en sesión.

Aplicables a la Etapa 0 (Cimientos), leídos íntegros antes de planificar:

| Skill | Aplicación en Etapa 0 |
|---|---|
| `lg-first-principles` | Cuestionar el "esqueleto estándar" antes de crearlo (ritual de apertura) |
| `lg-risk-radar` | Pre-mortem de la Etapa + registro vivo inicial |
| `lg-cto-mode` | Encaje del esqueleto con las 13 capas futuras sin crearlas vacías |
| `lg-critical-review` + `lg-metacognition` | Cierre del plan y de toda entrega |
| `pl-wp-core` | Ciclo de vida (activación/desactivación/uninstall), capacidades, scoping, i18n, wp-env |
| `pl-testing` | Mapa de tests del ciclo de vida, wp-env, convenciones desde el primer test |
| `pl-pipeline` | Solo lectura anticipatoria: el esqueleto no debe contradecir los contratos del Transicionador (Etapa 1) |

No aplicables aún (se releerán en su Etapa): `pl-periodistas` (E2), `pl-compuertas` (E3), `pl-proveedor-ia` (E1–E2). Del ecosistema global, para Etapa 0 solo tocan: `test-driven-development`, `e2e-testing`/`playwright-skill` (montaje de suites) y `php-pro` (estándares PHP 8.2).

## Apertura de Etapa 1 — "El esqueleto que camina" (2026-07-22)

Etapa 0 cerrada (CI en verde, run 29968501162). Inventario para Etapa 1 releído íntegro:

| Skill | Aplicación en Etapa 1 |
|---|---|
| `pl-pipeline` (+ `references/estados.md`) | Núcleo de la etapa: grafo de estados de la Pieza, Transicionador, candado global+por-Pieza, presupuestos, azar, perecibilidad |
| `pl-proveedor-ia` (+ `references/contrato-lenguaje.md`) | `SensorInterface` para el Radar; `LenguajeInterface` SOLO si Etapa 1 decide generar contenido real (ver pregunta de alcance en el plan) |
| `pl-wp-core` | dbDelta de tablas `pluma_*` nuevas, endpoint del cron con token+candado, creación del post WP al final del pipeline |
| `pl-testing` | Convenciones ya usadas en E0 (RelojInterface/AzarInterface inyectables) + nuevo: doble ejecución del orquestador, muerte a mitad de lote, fixtures de proveedores sin red |
| `lg-first-principles`, `lg-risk-radar`, `lg-cto-mode` (lente Cerebro), `lg-critical-review`, `lg-metacognition` | Ritual de apertura completo (igual que Etapa 0) |

Sub-agentes de AGENTS.md que se activan: **ESQUEMA** (tablas `pluma_piezas`, `pluma_tendencias`, `pluma_fuentes`, `pluma_bitacora_motor`, `pluma_auditoria`), **ORQUESTADOR** (motor cron, candado, presupuesto de tiempo), **SEGURIDAD** (endpoint del cron con token rotable + rate limit).

No aplicables aún: `pl-periodistas` (E2 — el redactor con diales no existe hasta entonces), `pl-compuertas` (E3). Hueco a decidir con el propietario antes de codificar: la Sala de Redacción completa es Etapa 2, pero el criterio de salida de Etapa 1 pide un "borrador trazable, aunque sea rudimentario" — el plan de Etapa 1 propone el alcance exacto y lo deja pendiente de aprobación.
