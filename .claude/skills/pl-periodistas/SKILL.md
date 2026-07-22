---
name: pl-periodistas
description: PLUMA — Periodistas sintéticos. Usar al tocar identidad, diales de conducta, matriz de tonos, memoria editorial, algoritmo de decisión (asignación, ángulo, tesis), redacción en dos pasadas, Corrector Interno o bloque del editor. Es el corazón del producto: leer completo antes de tocar Pluma\Redaccion.
---

# PLUMA — Periodistas Sintéticos

## Contratos innegociables
1. **Cuatro capas persistentes**: Identidad, Conducta (diales 0–100 + reglas cualitativas + matriz de tonos), Memoria, Repertorio. Toda modificación de Conducta crea versión fechada; cada Pieza registra qué versión usó (trazabilidad + coherencia histórica).
2. **El redactor solo conoce el expediente.** Cero conocimiento externo en el borrador: toda afirmación trazable a un hecho del expediente con su estado (verificado/atribuido/disputado). El Corrector Interno lo verifica; los tests verifican al Corrector (GOVERNANCE §2.4).
3. **Memoria antes de tesis**: el registro de posturas se consulta ANTES de seleccionar ángulo. Contradicción → reconocimiento explícito en el texto o cambio de tesis. Silenciar la contradicción es bug crítico.
4. **Matriz de tonos**: dominante + apoyo por tipo de noticia. La regla de sistema (sátira/humor bloqueados en tragedia, menores, víctimas) se aplica aquí Y en Compuertas — defensa en profundidad, dos capas independientes.
5. **Corrector Interno**: agente separado con checklist de 6 puntos (hechos, proporción interpretativa ≥60%, solapamiento n-grama, voz, titular honesto, matriz/líneas rojas). Máximo 2 ciclos → RETENIDA. Jamás "aprobar lo menos malo".
6. **Voz medible**: rasgos estilísticos presentes con frecuencia controlada, vocabulario prohibido ausente (incluida la lista de muletillas de texto IA de `references/conducta.md`). Es un test, no una aspiración.
7. **Ficha de Decisión Editorial** completa en toda Pieza: periodista+versión, candidatos de tesis puntuados, tesis elegida, tonos, esqueleto. Sin ficha completa no hay paso a redacción.
8. **Export/import del banco** (periodistas + memoria) es API pública del producto: todo cambio de esquema mantiene compatibilidad de import o migra explícitamente.

## Referencias
- `references/conducta.md` — catálogo de diales, plantilla de matriz, listas de vocabulario prohibido y rasgos de voz.
