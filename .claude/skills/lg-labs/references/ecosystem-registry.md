# LG Labs — Registro del ecosistema (capa de conocimiento) · Edición PLUMA Engine
Evaluación: 2026-07-22 · Sustituye al registro del proyecto anterior (SocialPlant/Ghost Armada), que se conserva en el origen canónico de LG.

**Qué es esto.** Mapa curado de skills de conocimiento que vale la pena *alcanzar deliberadamente* en PLUMA Engine. No se copian dentro de los skills LG; se catalogan por propósito para no reinventar. El inventario operativo con veredictos vive en `docs/skills-descubiertas.md` del repo (protocolo SKILLS-STACK §2); este registro es su vista desde la capa LG.

**Caveat de honestidad (lg-research-depth).** Evaluación por propósito declarado; la calidad real se confirma al invocar cada skill. Si una decepciona, se elimina de aquí y del inventario.

**Contexto del proyecto.** PLUMA Engine (LG Dev Studio): plugin comercial de WordPress (PHP 8.2, Composer+Scoper, tablas propias, cron real), sala de redacción sintética con periodistas parametrizables, compuertas editoriales y publicación autónoma; panel React/Vite sobre la REST API de WP; proveedores externos de lenguaje y tendencias.

## Registro por categoría

### Skills de proyecto (capa de conocimiento primaria — en `skills/` del repo)
| Skill | Para qué la alcanzo | Relevancia |
|---|---|---|
| `pl-pipeline` | Grafo de estados, orquestador, idempotencia, cuotas | Alta |
| `pl-periodistas` | Diales, matriz de tonos, memoria, Corrector Interno | Alta |
| `pl-compuertas` | Invariantes editoriales, degradación por sensibilidad | Alta |
| `pl-wp-core` | Hooks, REST, dbDelta, i18n, ciclo de vida, convivencia | Alta |
| `pl-proveedor-ia` | Contrato de lenguaje, anti-inyección, coste, resiliencia | Alta |
| `pl-testing` | Mapa de tests, wp-env, dobles, suite de invariantes | Alta |

### Skills técnicos reutilizables de la empresa (origen: ecosistema LG)
| Skill | Para qué | Relevancia |
|---|---|---|
| `http-client-resilience` | Timeouts, backoff, circuit breaker en Pluma\Proveedores | Alta |
| `exception-handling-and-logging` | Bitácora del motor y manejo de errores del pipeline | Alta |
| `ui-ux-pro-max` | Sistema de diseño del panel (tokens, tipografía, estados) | Alta |
| `frontend-design` | Dirección estética del panel premium | Alta |
| `kpi-dashboard-design` | La Portada (cap. 10): jerarquía de métricas accionables | Media-Alta |
| `tailwind-design-system` | Tokens del panel (adaptar al stack React del panel) | Media |
| `web-design-guidelines` | Frontend público mínimo (bloque del editor) | Media |
| `spatie-security` | Principios de roles/permisos → traducir a capacidades WP | Media (adaptar) |
| `laravel-specialist` · `filament-pro` · `pest-testing` · `queues-and-horizon` | Patrones de colas/testing como inspiración | Baja (NO aplican directo: stack Laravel) |
| `ri-*` (RoyalImage) | Plantilla estructural de los skills `pl-*` | Meta |
| `skill-creator` | Estándar de autoría al crear skills nuevos | Alta (para LG mismo) |

## Rechazos explícitos
- Skills de framework ajeno al stack (Laravel/Filament como dependencia, no como patrón): fuera del código de PLUMA.
- Pesos pesados de distribuido/cloud (microservicios, k8s, suites cloud): sobreingeniería para un plugin — fuera hasta que lg-future-thinking lo dispare.
- Skills de persona: inspiración puntual, no protocolo. Fuera del stack.

## Huecos de conocimiento detectados (investigar en fuente oficial; candidatos a skill nuevo si se usan >1 vez)
1. **Interiores de WordPress avanzados** (REST auth, dbDelta fino, multisitio) → cubierto parcialmente por `pl-wp-core`; ampliar con el Developer Handbook.
2. **schema.org de noticias** (NewsArticle/OpinionNewsArticle/AnalysisNewsArticle) y **sitemap de Google News** — sin skill; fuente oficial obligatoria.
3. **APIs de Google** (Trends no-oficial vs alternativas, Search Console, Indexing) — sin skill; decisión de proveedor vía LG Decision Framework.
4. **Licenciamiento y update server de plugins comerciales** — sin skill; dominio del sub-agente RELEASE.
