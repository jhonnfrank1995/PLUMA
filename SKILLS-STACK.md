# SKILLS-STACK.md — PLUMA Engine
# Stack de Skills, Herramientas y Protocolo de Descubrimiento

---

## CRITERIO DE SELECCIÓN

**INCLUIR** cuando el componente: hace cumplir una regla que el modelo no garantiza solo · detecta defectos que otros no ven · reduce defectos en el dominio específico de PLUMA · añade cero ruido de contexto.
**EXCLUIR** cuando: duplica CLAUDE.md/GOVERNANCE.md · consume contexto sin reducir defectos · genera ruido que no cambia conducta.

---

## 1. CAPA DE RAZONAMIENTO — LG LABS (obligatoria, se reutiliza, no se copia)

El stack de pensamiento de LG Dev Studio (`lg-labs` + sus 13 skills) gobierna CÓMO se piensa en este proyecto. Copia de trabajo incluida en `.claude/skills/` de este repo (origen: ecosistema LG; el índice es `.claude/skills/lg-labs/SKILL.md`). **Instalación**: por-proyecto en `.claude/skills/` — el repo es autocontenido y el agente descubre estos skills automáticamente al abrir PLUMA, sin depender de una instalación global en la máquina. Regla de mantenimiento: la versión canónica vive en el repositorio de skills de LG; ante divergencia, se sincroniza desde allí y JAMÁS se editan localmente en PLUMA.

| Momento en PLUMA | Combo LG obligatorio |
|---|---|
| Módulo o feature nueva | First Principles → Product Vision → CTO Mode → Risk Radar → Critical Review → Decision Framework |
| Decisión de stack/proveedor (IA, tendencias, licencias) | Research Depth → Innovation Engine → Future Thinking → Independence → Decision Framework |
| Pantalla del panel | Product Vision → Design Excellence → Critical Review |
| Bug recurrente (3ª aparición) | Research Depth → CTO Mode (lente sistemas) → Critical Review |
| El orquestador y todo lo autónomo | CTO Mode **lente Cerebro** completa (percepción-memoria-decisión-acción-homeostasis). Un sistema autónomo sin homeostasis es una bomba: diséñala primero. |

**Metacognition corre siempre al final** (anti-complacencia: ¿estoy entregando al 100% o estoy agradando?). **Research Depth corre por debajo** (toda afirmación sobre APIs de WP/Google/proveedores lleva evidencia de docs oficiales, no memoria).

---

## 2. PROTOCOLO DE DESCUBRIMIENTO DE SKILLS (obligatorio antes de la Etapa 1 y ante cada dominio nuevo)

Antes de escribir código de un dominio, el agente DEBE inventariar qué conocimiento ya existe y registrar el resultado:

1. **Inventario local**: listar skills disponibles en el entorno (`/mnt/skills/**` o el directorio de skills del proyecto) y en los repositorios del ecosistema del propietario. Leer el SKILL.md de todo lo plausiblemente relevante — la relación tarea→skill no siempre es obvia por el nombre.
2. **Mapa de aplicabilidad**: para cada skill encontrada, veredicto en una línea: APLICA (y a qué módulo) / ADAPTAR (qué cambia para WordPress) / NO APLICA (por qué). Ejemplos del ecosistema actual: `exception-handling-and-logging` y `http-client-resilience` APLICAN a `Pluma\Proveedores`; `ui-ux-pro-max` y `frontend-design` APLICAN al panel; `laravel-specialist`/`filament-pro`/`pest-testing` NO APLICAN directo (stack Laravel) pero sus patrones de testing y colas se ADAPTAN; `kpi-dashboard-design` APLICA a la Portada del panel.
3. **Huecos**: dominio sin skill = investigar en fuentes oficiales (WordPress Developer Handbook, docs del proveedor) ANTES de codificar, y si el conocimiento se usará más de una vez, proponer un skill `pl-*` nuevo con sus referencias.
4. **Registro**: el mapa vive en `docs/skills-descubiertas.md` con fecha. Se revisa al iniciar cada Etapa del PLAN-MAESTRO.

---

## 3. SKILLS DE PROYECTO `pl-*` (viven en `.claude/skills/` de este repo)

| Skill | Cubre | Leer al tocar |
|---|---|---|
| `pl-pipeline` | Máquina de estados de Pieza, orquestador, contratos de eventos | `Pluma\Pipeline`, `Pluma\Publicacion` |
| `pl-periodistas` | Diales, matriz de tonos, memoria, decisión editorial, Corrector | `Pluma\Redaccion` |
| `pl-compuertas` | Las tres compuertas + invariantes editoriales como tests | `Pluma\Compuertas`, cualquier ruta hacia publicar |
| `pl-wp-core` | Estándares WordPress: hooks, REST, capacidades, dbDelta, i18n, scoping | cualquier integración con WP |
| `pl-proveedor-ia` | Contrato LenguajeInterface, anti-inyección, coste, fixtures | `Pluma\Proveedores` |
| `pl-testing` | Mapa de tests, wp-env, dobles de proveedores, semillas de azar | cualquier test |

---

## 4. HERRAMIENTAS OBLIGATORIAS (activas en toda tarea)

| Herramienta | Propósito | Gate |
|---|---|---|
| PHPStan L8 + `szepeviktor/phpstan-wordpress` | Tipos + stubs de WP: elimina alucinación de APIs de WordPress | `composer analyse` = 0 |
| PHPCS `WordPress-Extra` + `WordPress.Security` | Estilo + sanitización/escape/nonces detectados estáticamente | `composer lint` = 0 |
| PHPUnit + Brain\Monkey (unit) / `wp-env` (integración) | Lógica pura sin WP + repositorios y REST sobre WP real | `composer test` verde |
| Playwright | E2E: onboarding, ciclo de Pieza, veto móvil, panel | `npx playwright test` verde |
| Vitest | Lógica pura JS del panel | `npm test` verde |
| PHP-Scoper + build script | Paquete de venta sin colisiones de dependencias | ZIP smoke test |
| `composer audit` + auditoría npm | CVEs bloquean release | `/qa` |

**Puerta `/qa` completa**: lint → analyse → test → npm test → build → audit. Todo 0 antes de DONE.
