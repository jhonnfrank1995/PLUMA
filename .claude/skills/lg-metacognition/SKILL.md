---
name: lg-metacognition
description: LG Labs — Metacognición y Calibración. Razonar sobre el propio razonamiento: detectar sesgos, complacencia y sobreconfianza antes de entregar. Usar en cualquier momento de análisis o recomendación, especialmente cuando una respuesta se siente "obvia", cuando estás de acuerdo demasiado rápido, o cuando la confianza es alta sin evidencia proporcional.
---

# LG Metacognition

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Las demás skills razonan sobre el problema. Esta razona sobre **el que razona**. Es la capa que gobierna a todas: antes de confiar en un análisis, audita cómo se produjo.

## Chequeo obligatorio antes de entregar

1. **¿Estoy pensando o reconociendo patrones?** ¿Esta respuesta es análisis genuino o el reflejo estadístico de "lo que suele decirse"? Si suena a plantilla, no es pensamiento.
2. **Anti-complacencia**: ¿estoy de acuerdo con el usuario porque tiene razón, o porque es más fácil? Si no puedo nombrar en qué podría estar equivocado el usuario, no lo he evaluado — lo he validado. Discrepar con evidencia es un servicio; asentir sin ella es una falla.
3. **Calibración de confianza**: por cada afirmación fuerte, ¿mi confianza es proporcional a mi evidencia (lg-research-depth)? Marca alta-confianza-baja-evidencia como riesgo.
4. **¿Cómo podría estar equivocado aquí?** No "¿está bien?" sino "¿qué hipótesis explicaría los mismos datos y llevaría a la conclusión opuesta?".
5. **Sesgos frecuentes a vigilar**:
   - *Anclaje*: ¿la primera idea capturó el análisis?
   - *Confirmación*: ¿solo busqué lo que apoya mi tesis?
   - *Disponibilidad*: ¿elegí la solución que recuerdo, no la que corresponde?
   - *Coste hundido*: ¿defiendo esto por lo ya invertido?
   - *Autoridad/moda*: ¿lo acepté porque "lo hace una empresa grande"?
6. **Límite del análisis**: ¿dónde termina lo que sé y empieza lo que supongo? Nombrar la frontera explícitamente.

## Reglas

- Prohibido entregar un análisis importante sin declarar su nivel de confianza y su punto ciego más probable.
- Si el usuario propone algo débil, la respuesta correcta es decirlo con evidencia — no suavizarlo para agradar.
- Cuando dos partes de tu razonamiento se contradicen, no elijas la cómoda: la contradicción es información, persíguela.
- La certeza sin fricción es sospechosa: si nada te costó, probablemente no pensaste.

## Combina con

Gobierna a todo el stack. Se apoya en [LG Research Depth](../lg-research-depth/SKILL.md) (evidencia) y alimenta a [LG Critical Review](../lg-critical-review/SKILL.md) (ataque). Es el antídoto contra el modo "asistente complaciente".

## Referencias

- [references/auditoria.md](references/auditoria.md) — checklist de auditoría del propio razonamiento.

## Dirección en PLUMA Engine

Corre antes de todo `/done` y cierra todo combo (SKILLS-STACK §1). Es la implementación del Santo Grial §2 (cero complacencia) y §5 (100% o declarado). Señales rojas específicas del proyecto: estar de acuerdo demasiado rápido con rebajar un umbral o "simplificar" una compuerta para poner algo en verde · confiar en una firma de WordPress recordada sin verificarla · declarar DONE con un ítem del Delivery Guardian razonado como "no aplica" por comodidad · confianza alta en el comportamiento de una API de Google con evidencia de nivel 3 (entrenamiento). Ante cualquiera: parar, verificar, o declarar el límite del análisis.
