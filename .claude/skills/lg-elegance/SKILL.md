---
name: lg-elegance
description: LG Labs — Elegancia y Simplicidad. Buscar la solución más simple, elegante y mantenible; combatir la complejidad accidental y hacer sustracción. Usar al diseñar módulos, APIs, esquemas o cualquier estructura, y al revisar código/arquitectura que "creció" — la mejor pieza suele ser la que se elimina.
---

# LG Elegance

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

La elegancia no es estética: es la propiedad de un sistema que hace lo necesario con las mínimas partes móviles, y por eso escala y se mantiene. Tu trabajo es encontrar la solución más simple que resuelve el problema completo — no la más simple que parece resolverlo.

## Distinción fundamental

- **Complejidad esencial**: la que el problema realmente tiene. Se respeta.
- **Complejidad accidental**: la que añadimos nosotros (abstracciones prematuras, capas de más, configuración, casos especiales). Se elimina sin piedad.

La mayoría de la complejidad de un sistema es accidental. Cazarla es el trabajo.

## Proceso

1. **Sustracción primero**: antes de añadir, pregunta qué puedes quitar. La mejor línea de código es la que no existe; el mejor módulo, el que no hace falta.
2. **Presupuesto de complejidad**: cada abstracción, dependencia, opción y caso especial tiene un coste permanente de mantenimiento. ¿El beneficio lo paga? Si no, fuera.
3. **Cuenta las partes móviles**: entre dos soluciones que funcionan, gana la de menos conceptos que el próximo desarrollador debe cargar en la cabeza.
4. **Un buen nombre vale más que un comentario**: si algo necesita mucha explicación, probablemente está mal cortado.
5. **Elegancia estructural**: ¿los límites entre piezas caen donde el problema tiene sus junturas naturales, o son arbitrarios? Un corte elegante hace que el cambio típico toque un solo lugar.
6. **Regla de tres**: no abstraigas hasta el tercer caso real. La abstracción especulativa es complejidad accidental disfrazada de previsión.

## Reglas

- Prohibido "por si acaso": toda flexibilidad no usada hoy es deuda pagada por adelantado sin recibo.
- Simple ≠ fácil ni ≠ pocas líneas: una línea densa e ilegible no es elegante. Simple = pocos conceptos, claros.
- Si no puedes explicar el diseño a otro en dos frases, aún no está terminado.
- La elegancia se somete a la realidad: si el caso esencial es complejo, la solución honesta lo refleja — no lo esconde bajo una simplicidad falsa.

## Combina con

Recibe de [LG First Principles](../lg-first-principles/SKILL.md) (la reconstrucción mínima). Sirve a la mantenibilidad de [LG CTO Mode](../lg-cto-mode/SKILL.md) y a la evolución de [LG Future Thinking](../lg-future-thinking/SKILL.md). Su versión de UX es [LG Design Excellence](../lg-design-excellence/SKILL.md).

## Referencias

- [references/complejidad.md](references/complejidad.md) — auditoría esencial vs. accidental + presupuesto de complejidad.

## Dirección en PLUMA Engine

Complejidad esencial declarada: el grafo de estados, las tres compuertas, la separación instrucciones/material del proveedor — no las escondas bajo simplicidad falsa. Cacería de accidental típica: ¿un estado nuevo o basta un motivo en la transición?, ¿un sensor abstracto o regla de tres (esperar al tercero real)?, ¿una opción nueva del motor o un default de fábrica? El presupuesto de complejidad del panel se paga en soporte postventa: cada concepto de más es un ticket futuro.
