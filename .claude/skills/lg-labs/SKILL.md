---
name: lg-labs
description: LG Labs — Índice y protocolo del stack de pensamiento de LG Dev Studio. Invocar para saber qué skill de pensamiento aplicar, o para orquestar varias en una decisión importante (feature nueva, arquitectura, elección de stack, revisión de producto). Es el mapa y el director de orquesta de los 13 skills LG + el registro de la capa de conocimiento del ecosistema.
---

# LG Labs — Stack de Pensamiento

> **Edición PLUMA Engine v2.0** (2026-07-22) — índice canónico LG + capa de conocimiento apuntando a los skills `pl-*` y al registro del proyecto.

Trece skills de pensamiento de LG Dev Studio. No son módulos sueltos: forman un sistema que va de **divergir** (romper, imaginar, investigar) a **converger** (criticar, decidir), con dos disciplinas transversales que gobiernan a todas. Este índice dice cuál usar y en qué orden.

## Modelo de dos capas

- **Capa de razonamiento** = estos 12 skills LG. Es el *sistema de pensamiento*; sube la calidad del análisis. Siempre relevante.
- **Capa de conocimiento** = skills del ecosistema que se *alcanzan* cuando el problema toca un dominio técnico. En PLUMA Engine son: los skills de proyecto `pl-*` (pipeline, periodistas, compuertas, wp-core, proveedor-ia, testing — en `skills/` del repo) y los skills técnicos reutilizables de la empresa catalogados en [references/ecosystem-registry.md](references/ecosystem-registry.md). Añaden datos, no juicio.

Razona siempre con la capa LG; consulta el registro solo cuando el dominio lo pida.

## Los 13 skills

| Skill | Para qué | Fase |
|---|---|---|
| [LG First Principles](../lg-first-principles/SKILL.md) | Romper paradigmas, cuestionar supuestos, reconstruir desde cero | Divergir |
| [LG Product Vision](../lg-product-vision/SKILL.md) | Producto de clase mundial + diferenciación | Divergir |
| [LG Innovation Engine](../lg-innovation-engine/SKILL.md) | Prohibir lo común, construir fosos difíciles de copiar | Divergir |
| [LG Future Thinking](../lg-future-thinking/SKILL.md) | Horizonte 5–10 años, reversibilidad, evolución, tech emergente | Divergir |
| [LG CTO Mode](../lg-cto-mode/SKILL.md) | Arquitecto responsable: estrategia + sistemas + cerebros | Construir |
| [LG Design Excellence](../lg-design-excellence/SKILL.md) | Filosofía de UX + identidad visual coherente | Construir |
| [LG Elegance](../lg-elegance/SKILL.md) | Simplicidad, sustracción, combatir complejidad accidental | Construir |
| [LG Independence](../lg-independence/SKILL.md) | Independencia tecnológica, modularidad, anti-lock-in | Construir |
| [LG Risk Radar](../lg-risk-radar/SKILL.md) | Pre-mortem: anticipar y gestionar riesgos antes de construir | Converger |
| [LG Critical Review](../lg-critical-review/SKILL.md) | Autocrítica + abogado del diablo con contrapropuesta | Converger |
| [LG Decision Framework](../lg-decision-framework/SKILL.md) | Trade-offs explícitos → una decisión con registro | Converger |
| [LG Research Depth](../lg-research-depth/SKILL.md) | Evidencia bajo cada afirmación | Transversal |
| [LG Metacognition](../lg-metacognition/SKILL.md) | Razonar sobre el propio razonamiento; anti-sesgo y anti-complacencia | Transversal (gobierna) |

## Combos (secuencias recomendadas)

- **Feature o producto nuevo**: First Principles → Product Vision → Innovation Engine → (CTO Mode / Design Excellence / Elegance) → Risk Radar → Critical Review → Decision Framework.
- **Decisión de arquitectura o stack**: First Principles → CTO Mode → Future Thinking → Elegance → Independence → Risk Radar → Critical Review → Decision Framework.
- **Diseño de pantalla/flujo (UI)**: Product Vision → Design Excellence → Critical Review.
- **Diagnóstico de bug recurrente**: Research Depth → CTO Mode (lente de sistemas) → Critical Review.
- **Elegir entre proveedores/tecnologías**: Research Depth → Innovation Engine → Future Thinking → Decision Framework.
- **Antes de comprometerse a construir algo grande**: Risk Radar (pre-mortem) → Decision Framework.

En todo combo, **Metacognition** corre al final como filtro (¿me estoy engañando?) y **Research Depth** corre por debajo (¿tengo evidencia?).

## Protocolo operativo

1. **Investiga antes de afirmar** (Research Depth es transversal: cada afirmación clave lleva su nivel de evidencia).
2. **Diverge antes de converger**: nunca saltes a decidir sin haber roto supuestos y generado alternativas reales.
3. **Ataca antes de entregar**: ninguna propuesta importante se entrega sin pasar por Critical Review.
4. **Registra lo irreversible**: toda decisión de una dirección deja registro (Decision Framework).
5. **Ajusta la profundidad al peso**: una puerta de dos direcciones no merece el ritual completo — decide rápido y sigue. El rigor se reserva para lo caro de revertir.

## Referencias

- [references/mapa.md](references/mapa.md) — diagrama del flujo divergir→converger y tabla de disparadores.

## Dirección en PLUMA Engine

Combos ya cableados en la gobernanza del proyecto (SKILLS-STACK §1 de pluma-engine): módulo/feature nueva, decisión de proveedor, pantalla del panel, bug recurrente, y — específico de este producto — **todo lo autónomo pasa por la lente Cerebro de CTO Mode** (el orquestador es un cerebro: percepción-memoria-decisión-acción-homeostasis). Metacognition cierra cada `/done`; Research Depth sostiene toda afirmación sobre WordPress, Google y proveedores con fuente. La capa de conocimiento del proyecto son los seis skills `pl-*`; se consultan cuando el dominio lo pide, el juicio siempre es de esta capa.
