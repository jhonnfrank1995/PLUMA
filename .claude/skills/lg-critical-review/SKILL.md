---
name: lg-critical-review
description: LG Labs — Revisión Crítica. Criticar y mejorar las propias propuestas antes de entregarlas. Autocrítica durante el diseño + ataque adversarial final con contrapropuesta. Usar como paso obligatorio tras completar cualquier plan, arquitectura, diseño o recomendación, antes de darla por definitiva.
---

# LG Critical Review

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Una propuesta no está terminada cuando funciona en tu cabeza; está terminada cuando sobrevive a un ataque genuino. Dos fases: autocrítica integrada y demolición adversarial.

## Fase 1 — Autocrítica (mientras diseñas)

Toda propuesta significativa incluye una sección **"Autocrítica"** que responde:
1. **¿Por qué esta solución es mala?** ≥2 debilidades reales, no cosméticas.
2. **¿Cómo podría fallar?** Modos concretos: carga, datos corruptos, API/plataforma que cambia, error humano, concurrencia (dos instancias a la vez).
3. **¿Qué pasa en 5 años?** Dependencias muertas, supuestos rotos, equipo distinto.
4. **¿Qué limitaciones tiene?** Casos que nunca cubrirá bien.
5. **¿Cómo la reemplazaría?** Diseña la salida antes de entrar; estima el coste de reversión (bajo / medio / prohibitivo).

## Fase 2 — Abogado del diablo (al terminar)

Cámbiate de bando: eres un competidor brillante cuyo objetivo es que esta propuesta fracase.
1. **Ataca en tres frentes**: fáctico (¿premisa falsa/vieja?), lógico (¿la conclusión no se sigue?), de marco (¿resuelve el problema equivocado?).
2. **Construye la contrapropuesta**: la mejor alternativa que un rival defendería, defendible por sí misma (no un espantapájaros).
3. **Enfrenta ambas** con los mismos criterios.
4. **Veredicto honesto**: mantener la original, adoptar la contrapropuesta, o sintetizar.

## Reglas

- El ataque debe ser genuino: si no encuentras nada que cambie la decisión, dilo explícitamente — eso da confianza, no es un fracaso.
- Prohibido el teatro: objeciones débiles elegidas para refutarse fácil invalidan el ejercicio.
- Si la crítica revela una debilidad fatal, descarta y rehaz; no entregues con parches.
- Solo debilidades que cambiarían la decisión. "Podría documentarse mejor" no cuenta.

## Combina con

Cierra a [LG CTO Mode](../lg-cto-mode/SKILL.md), [LG Product Vision](../lg-product-vision/SKILL.md) y [LG Design Excellence](../lg-design-excellence/SKILL.md). Sus ataques usan la evidencia de [LG Research Depth](../lg-research-depth/SKILL.md). El veredicto pasa a [LG Decision Framework](../lg-decision-framework/SKILL.md).

## Referencias

- [references/demolicion.md](references/demolicion.md) — modos de fallo típicos + líneas de ataque por tipo de propuesta.

## Dirección en PLUMA Engine

Es el paso final de todo /done (Delivery Guardian). Líneas de ataque propias del proyecto: ¿esta pieza del pipeline puede publicar algo rodeando una compuerta? (frente fáctico sobre los invariantes) · ¿el borrador que produce pasaría la revisión de un editor humano exigente? · ¿la pantalla sobrevive al uso desde un móvil con prisa? · ¿la decisión de proveedor tiene contrapropuesta real con costes? · ¿qué rompería este cambio en la instalación de un cliente con Yoast + caché + multisitio? Si el ataque no encontró nada que cambiaría la decisión, decláralo — y que Metacognition verifique que no fue teatro.
