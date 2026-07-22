# ECOSYSTEM.md — PLUMA Engine
# Capa operativa de Claude Code — configuración del entorno del agente

---

## PRINCIPIO RECTOR
Mínimo ruido de contexto, máxima calidad técnica. Cada componente instalado: (a) hace cumplir una regla de gobernanza, o (b) da acceso a información verificable del sistema real. Todo lo demás, fuera.

## UBICACIÓN
Toda la capa (CLAUDE.md, GOVERNANCE.md, AGENTS.md, SKILLS-STACK.md, este archivo, `.claude/skills/`, `docs/`) vive en la raíz del repo `pluma-engine/`. Las sesiones de Claude Code se abren ahí.

## 1. HOOKS (`.claude/settings.json` + `.claude/hooks/`)
| Hook | Evento | Función |
|---|---|---|
| `php-quality-gate` | PostToolUse (Edit/Write en `*.php`) | `php -l` + presencia de `declare(strict_types=1)` en `src/` + `phpcs` del archivo. Exit 2 → el agente corrige en el momento. Rápido (<2 s); PHPStan y suites viven en `/qa`. |
| `no-placeholder-gate` | PostToolUse (Edit/Write) | Grep de `TODO|FIXME|Not implemented|lorem ipsum|placeholder` en el archivo tocado. Encontrado → violación del Santo Grial §3, corregir o registrar en `docs/deuda.md`. |

## 2. COMMANDS (`.claude/commands/`)
| Comando | Propósito | Norma |
|---|---|---|
| `/qa` | lint → analyse → test → npm test → build → audit | GOVERNANCE §4.5 |
| `/done` | Delivery Guardian completo | CLAUDE.md |
| `/nueva-etapa <n>` | Abre Etapa del PLAN-MAESTRO: re-ejecuta descubrimiento de skills + inventario + riesgos | SKILLS-STACK §2 |
| `/nuevo-modulo <capa>` | Protocolo de módulo: contrato, eventos, tests nombrados, encaje de capa | CLAUDE.md § Ley de Arquitectura |
| `/auditoria-invariantes` | Corre solo la suite de invariantes editoriales | GOVERNANCE §2 |
| `/release <x.y.z>` | Checklist del sub-agente RELEASE de punta a punta | GOVERNANCE §5 |

## 3. MCP RECOMENDADOS
| Servidor | Valor |
|---|---|
| Base de datos (inspección del esquema `pluma_*` y `wp_*` real) | El agente ve el esquema, no lo infiere |
| Docs oficiales (WordPress Developer Resources, docs del proveedor de IA) | Elimina alucinación de APIs; obligatorio consultarlo ante cualquier firma dudosa (Santo Grial §4) |
| Playwright/navegador | Verificación E2E del panel y del frontend público |

## 4. REGISTRO DE CONOCIMIENTO DEL ECOSISTEMA
La capa de conocimiento reutilizable del propietario (skills técnicos de otros proyectos) se cataloga en `docs/skills-descubiertas.md` según el protocolo de SKILLS-STACK §2. Regla: se ALCANZA cuando el dominio lo pide; jamás se copia dentro de PLUMA (una sola fuente de verdad por skill).

## 5. MANTENIMIENTO
- Tras `composer update` / `npm update`: `/qa` completo + smoke del ZIP.
- Revisión trimestral de este archivo: componentes que no cambiaron conducta del agente en el trimestre se retiran (criterio de EXCLUIR de SKILLS-STACK).
