---
name: pl-compuertas
description: PLUMA — Las tres compuertas (calidad, riesgo, originalidad) y los invariantes editoriales como tests. Usar al tocar Pluma\Compuertas, cualquier código que pueda desembocar en publicación, la degradación de modos por sensibilidad, o los umbrales. Sistema inmunológico del producto: aquí no hay atajos.
---

# PLUMA — Compuertas e Invariantes

## Contratos innegociables
1. **Toda ruta hacia publicar atraviesa las tres compuertas** y deja registro en `pluma_auditoria`. Un test de arquitectura lo garantiza: la única llamada legal a `Publicacion\CreadorPost` está detrás del evaluador de compuertas.
2. **Prohibido el bypass**: no existe flag, modo debug, filtro de WP ni opción que desactive compuertas en producción. Un `apply_filters` que permita a terceros saltarlas es una vulnerabilidad, no una extensión.
3. **Degradación por sensibilidad** (tragedia/menores/salud/acusaciones): fuerza Copiloto o Piloto y bloquea sátira aunque el modo global sea Autónomo. Regla de sistema, en dos capas (aquí y en Redacción).
4. **Riesgo de difamación**: afirmación fáctica negativa sobre persona identificable exige doble fuente `verificada` o redacción como opinión/atribución explícita; si no → RETENIDA para humano. En esta categoría el sistema nunca decide solo.
5. **Originalidad**: n-gramas contra los extractos del expediente + contra el propio sitio + heurística de ganancia de información. Sin ganancia → no sale (implementación anti scaled-content).
6. **Umbrales configurables, pisos de fábrica no**: el editor puede subir umbrales; los pisos de fábrica (los que protegen de Google y de demandas) son constantes del sistema.
7. **Tasa de retención observable**: compuertas que retienen 0% durante una semana disparan alerta — compuertas que nunca muerden están rotas.

## Al tocar un umbral o clasificador
Actualizar: constante/opción + test del invariante + fixture adversarial (casos que DEBEN ser retenidos) + pantalla de Sala de Revisión si cambia el diagnóstico mostrado.
