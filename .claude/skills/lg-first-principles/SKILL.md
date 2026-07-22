---
name: lg-first-principles
description: LG Labs — Primeros Principios. Romper paradigmas heredados y reconstruir desde cero, haciendo explícito y cuestionando todo supuesto. Usar al iniciar cualquier diseño, feature, refactor o decisión; se activa con "diseña", "propón", "cómo hacemos", "hay que", o cuando la solución obvia es copiar algo que ya existe.
---

# LG First Principles

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Antes de proponer nada, prohibido partir de una analogía ("un mejor X", "como Y pero…"). Descompón el problema hasta sus verdades irreducibles, haz explícito cada supuesto oculto, y reconstruye desde cero.

## Proceso

1. **Cuestiona la existencia**: ¿por qué existe esta pieza (bot, módulo, pantalla, paso)? ¿Debe existir? ¿Qué pasaría si no existiera?
2. **Aísla el problema real**: enúncialo en una frase, despojado de toda tecnología e implementación.
3. **Inventaría los supuestos**: lista TODO lo que estás dando por hecho (de la petición, del código heredado, de "mejores prácticas", de tu propio reflejo). Palabras señal: *obviamente, siempre, hay que, se necesita un, el estándar es*.
4. **Clasifica cada supuesto**:
   - *Axioma* — verdad inmutable (física, economía, plataforma, ley). Se conserva.
   - *Convención* — "siempre se hizo así" pero la razón caducó o nunca se verificó. Negociable.
   - *Inercia* — nadie recuerda por qué. Se elimina o se justifica de nuevo.
5. **Reconstruye**: diseña la solución mínima que satisface solo los axiomas.
6. **Busca el paradigma superior**: ¿existe una categoría de solución que hace irrelevante el problema entero?

## Reglas

- Ningún supuesto sobrevive por antigüedad: o se verifica, o se marca como riesgo abierto.
- "Es el estándar de la industria" no es argumento hasta nombrar el axioma que lo sostiene.
- Si la reconstrucción coincide con la convención, dilo y explica por qué sobrevivió al análisis (es un buen resultado, no un fracaso).
- Toda propuesta cierra con: **"Paradigma destruido / axiomas que quedaron / supuestos que verifiqué"**.

## Combina con

Es la puerta de entrada del stack. Después: [LG Product Vision](../lg-product-vision/SKILL.md) o [LG CTO Mode](../lg-cto-mode/SKILL.md) para reconstruir, e [LG Innovation Engine](../lg-innovation-engine/SKILL.md) para el paso 6.

## Referencias

- [references/descomposicion.md](references/descomposicion.md) — plantilla axiomas vs. convenciones + inventario de supuestos.

## Dirección en PLUMA Engine

Aplícalo al abrir cada Etapa del PLAN-MAESTRO y ante cada módulo. Supuestos heredados a romper siempre: los del mundo "auto-blogger" (¿por qué una pieza por tendencia?, ¿por qué el texto es la unidad y no la decisión editorial?, ¿por qué publicar es el objetivo y no la conversación?). Axiomas reales de PLUMA: políticas de contenido de Google (E-E-A-T, anti scaled-content), riesgo legal de difamación, economía de tokens de los proveedores, y la naturaleza de WordPress como plataforma anfitriona. Todo lo demás — incluida cualquier decisión del Libro de Arquitectura — es convención revisable con registro (LG Decision Framework).
