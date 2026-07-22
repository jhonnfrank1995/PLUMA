---
name: lg-research-depth
description: LG Labs — Investigación Profunda. Prohíbe respuestas superficiales — cada conclusión se justifica con evidencia y nivel declarado. Usar al hacer afirmaciones técnicas, comparar opciones, diagnosticar bugs o citar límites/comportamientos de plataformas externas (APIs, buscadores, proveedores de IA).
---

# LG Research Depth

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Ninguna conclusión sin justificación. Una afirmación sin evidencia es una hipótesis y debe presentarse como tal. Profundidad no es longitud: una respuesta profunda puede ser corta; lo que no puede es tener eslabones sin soporte.

## Niveles de evidencia (declara el de cada afirmación clave)

1. **Verificado** — lo comprobé en este proyecto (leí el código, ejecuté el comando, reproduje el comportamiento).
2. **Fuente** — documentación oficial o fuente primaria consultada (cítala).
3. **Conocimiento general** — lo sé de entrenamiento; puede estar desactualizado (marca si la fecha importa).
4. **Hipótesis** — razonable pero sin comprobar. Dilo explícitamente.

## Reglas

- Prohibido opinar sobre el código del proyecto sin leerlo: "probablemente el módulo X hace Y" se reemplaza por **leer X**.
- Toda comparación ("A es más rápido/estable/mejor que B") exige criterio + evidencia, o se degrada a hipótesis.
- Los números caducan (límites de API, cuotas, rendimiento): verifícalos con fuente actual si la decisión depende de ellos. Con conexión disponible, usa búsqueda antes de afirmar "no existe algo mejor".
- Al diagnosticar un bug: **reproducir > deducir**. Si no se puede reproducir, lista las hipótesis ordenadas por probabilidad y cómo descartar cada una.
- Falsabilidad: por cada conclusión importante, nombra qué observación la demostraría falsa. Si no existe, es demasiado vaga.

## Frases prohibidas sin evidencia adjunta

"normalmente", "debería funcionar", "es sabido que", "en general esto es más rápido".

## Combina con

Sostiene a todo el stack: las premisas de [LG First Principles](../lg-first-principles/SKILL.md), las comparaciones de [LG Decision Framework](../lg-decision-framework/SKILL.md) y los ataques de [LG Critical Review](../lg-critical-review/SKILL.md) deben apoyarse en este nivel de evidencia.

## Referencias

- [references/protocolo.md](references/protocolo.md) — protocolo de investigación antes de concluir.

## Dirección en PLUMA Engine

Nivel **Fuente** obligatorio (docs oficiales o MCP de documentación) para: toda firma de función/hook de WordPress antes de escribir la llamada (Santo Grial §4) · límites y precios de proveedores de lenguaje · APIs de Google (Trends, Search Console, Indexing) · schema.org (NewsArticle/OpinionNewsArticle) y protocolo de sitemap de noticias. Números que caducan rápido aquí: precios de tokens, cuotas de APIs, políticas de contenido de Google — verificar con fuente actual si la decisión depende de ellos. Frase prohibida estrella del dominio: "Google premia/penaliza X" sin fuente — se degrada a hipótesis o se elimina.
