---
name: lg-decision-framework
description: LG Labs — Marco de Decisión. Evaluar alternativas y trade-offs de forma explícita antes de decidir, y dejar registro de la decisión. Usar cuando hay que elegir entre opciones (stack, arquitectura, feature, proveedor, prioridad) — convierte la divergencia del resto del stack en una decisión defendible.
---

# LG Decision Framework

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

El resto del stack diverge (genera opciones, ataca, imagina). Este skill converge: transforma alternativas en **una** decisión razonada, con sus trade-offs a la vista y su lógica registrada. Prohibido decidir "por intuición" cuando la decisión es cara de revertir.

## Proceso

1. **Enmarca la decisión**: ¿qué se decide exactamente? ¿Cuál es el criterio de éxito? ¿Cuándo caduca esta decisión?
2. **Reversibilidad primero** (de [LG Future Thinking](../lg-future-thinking/SKILL.md)):
   - *Puerta de dos direcciones* → decide rápido con la opción razonable, no montes una matriz. La velocidad es el criterio.
   - *Puerta de una dirección* → aplica el proceso completo.
3. **Genera alternativas reales**: mínimo 3, incluida "no hacer nada" y la opción incómoda. Dos alternativas suele ser una falsa dicotomía.
4. **Define criterios ponderados**: 3–6 criterios que importan (no 15), con un peso cada uno. El peso se decide **antes** de puntuar, para no hacer trampa hacia la opción favorita.
5. **Puntúa con evidencia**: cada celda se apoya en [LG Research Depth](../lg-research-depth/SKILL.md), no en corazonada. Marca las celdas que son hipótesis.
6. **Trade-offs explícitos**: por la opción ganadora, nombra qué se sacrifica y qué tendría que ser verdad para que fuera la equivocada (condiciones de ruptura).
7. **Decide y registra**: una decisión, su razón, la fecha, los supuestos clave y la señal que obligaría a reconsiderar.

## Reglas

- Una matriz no decide por ti: si el número dice A pero tu instinto grita B, el desacuerdo revela un criterio o peso que faltó — encuéntralo, no ignores el instinto ni el número.
- Prohibido criterios inventados para justificar la opción ya elegida. Pesos antes que puntajes, siempre.
- Toda decisión importante deja un **registro** (aunque sean 5 líneas): dentro de un año nadie recuerda por qué, y sin registro se repite el debate o se revierte a ciegas.
- No decidir también es una decisión — con su propio coste. Nómbralo.

## Combina con

Es el embudo final del stack. Recibe opciones de [LG Innovation Engine](../lg-innovation-engine/SKILL.md), el veredicto de [LG Critical Review](../lg-critical-review/SKILL.md), la reversibilidad de [LG Future Thinking](../lg-future-thinking/SKILL.md) y el marco estratégico de [LG CTO Mode](../lg-cto-mode/SKILL.md).

## Referencias

- [references/matriz.md](references/matriz.md) — matriz de decisión ponderada + plantilla de registro (ADR ligero).

## Dirección en PLUMA Engine

Los registros viven en `docs/decisiones/` (ADR ligero, plantilla de references/matriz.md). Decisiones ya tomadas y registradas por el propietario — reabrirlas exige una señal de ruptura, no una preferencia: WordPress + PHP 8.2 como plataforma · tablas propias `pluma_*` (no post-meta) · cron real de servidor (no WP-Cron) · tres modos con degradación por sensibilidad · escasez honesta · pisos de fábrica de compuertas inamovibles. Toda elección de proveedor de lenguaje/tendencias pasa por matriz completa (son puertas caras: el coste por pieza define el margen del producto).
