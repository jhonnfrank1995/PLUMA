# LG Elegance — auditoría de complejidad

## Esencial vs. accidental
| Parte del sistema | ¿Complejidad esencial o accidental? | Si accidental: cómo eliminarla |
|---|---|---|

## Presupuesto de complejidad
Cada ítem tiene coste permanente. ¿El beneficio lo paga?
| Ítem (abstracción / dependencia / opción / caso especial) | Beneficio real hoy | Coste de mantenerlo | Veredicto |
|---|---|---|---|

## Sustracción
- ¿Qué módulo/clase/función podría no existir? ______
- ¿Qué opción de configuración es una decisión que no supimos tomar? ______
- ¿Qué flexibilidad "por si acaso" no se usa? ______ → eliminar

## Partes móviles
- Solución A: ___ conceptos que cargar en la cabeza
- Solución B: ___ conceptos
- ¿El corte entre piezas cae en las junturas naturales del problema? ______

## Test de dos frases
- Explica el diseño en dos frases: ______
- Si no se puede → no está terminado.
