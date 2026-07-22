---
name: lg-innovation-engine
description: LG Labs — Motor de Innovación. Prohíbe soluciones comunes por defecto y exige ventajas competitivas reales (fosos difíciles de copiar). Usar al proponer cualquier tecnología o patrón estándar (dashboard pasivo, scraping, polling, spinner de texto, plantilla genérica, CRUD por reflejo) y al priorizar en qué invertir esfuerzo de ingeniería.
---

# LG Innovation Engine

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Dos misiones: (1) prohibir la solución común por reflejo, y (2) asegurar que cada esfuerzo construye una ventaja difícil de copiar. Innovar no es novedad — es cambiar el techo de lo posible y hacerlo defendible.

## Parte A — Prohibido lo común por defecto

Cada vez que estés a punto de escribir una de estas (o equivalente), detente y pregunta **¿existe algo mejor?**:
- **Dashboard** → ¿el usuario necesita mirar datos, o que el sistema actúe solo y le avise?
- **Scraping** → ¿hay una fuente estructurada oficial (API, feed, evento) más alta y estable?
- **Persistencia por reflejo** → ¿el modelo pide relacional, o eventos/documentos/archivos?
- **Texto generado genérico** → ¿qué acumula el sistema (memoria, datos, criterio) que un generador sin estado jamás tendrá?
- **Polling / cron** → ¿existe push o suscripción?

Proceso: nombra la común → genera ≥2 alternativas de **paradigma distinto** (no marcas del mismo) → compara por fragilidad, mantenimiento, techo de calidad y ventaja competitiva → elige. Elegir la común es válido **solo después** de esto, nunca antes.

## Parte B — Ventaja competitiva real (fosos)

Clasifica todo lo que se construye:
- **Foso** — ventaja acumulativa difícil de replicar: datos propios que mejoran con el uso, efectos de red, know-how codificado (heurísticas anti-detección, tuning que costó meses), costes de cambio para el usuario, integraciones profundas.
- **Apuesta de foso** — aún no defiende, pero está diseñado para convertirse en foso (y puedes decir cómo/cuándo).
- **Mesa de entrada** — necesario para competir pero copiable en semanas. Constrúyelo al mínimo coste, sin orgullo.
- **Distracción** — ni defiende ni es necesario. Eliminar.

Preguntas por módulo: si un rival con más recursos lo viera funcionando, ¿cuánto tardaría en replicarlo? (días → mesa de entrada; años → foso). ¿Mejora con cada uso/dato? ¿La ventaja vive en el código (copiable) o en lo que el código acumula (datos, reputación, hábito)?

## Reglas

- "Es lo que todos usan" es la señal para activar este skill, no un argumento.
- Prohibido gastar semanas de ingeniería fina en algo clasificado como mesa de entrada.
- La elegancia técnica no es un foso: al competidor no le importa lo limpio que sea tu código.
- Si >70% del esfuerzo va a mesa de entrada, la estrategia tiene un problema — decláralo.

## Combina con

Recibe el paradigma superior de [LG First Principles](../lg-first-principles/SKILL.md) y la diferenciación de [LG Product Vision](../lg-product-vision/SKILL.md). Las alternativas emergentes se vigilan en [LG Future Thinking](../lg-future-thinking/SKILL.md).

## Referencias

- [references/alternativas-y-fosos.md](references/alternativas-y-fosos.md) — comparación de paradigmas + mapa de fosos.

## Dirección en PLUMA Engine

Lo común prohibido por defecto aquí: el "spinner" SEO, el auto-blogger de volumen, el dashboard que muestra sin decidir, el polling de tendencias sin puntuación. Mapa de fosos del producto: **foso** = memoria editorial acumulada por periodista + matrices de conducta afinadas con datos de audiencia + el historial de posturas (mejora con cada pieza publicada; un rival empieza de cero). **Apuesta de foso** = bucle de Search Console que aprende. **Mesa de entrada** = la capa SEO técnica (Yoast ya la tiene: construir correcta y al mínimo coste, sin orgullo). **Distracción** = toda feature que no alimente el criterio editorial ni la conversación. Regla del 70% vigilada por Etapa.
