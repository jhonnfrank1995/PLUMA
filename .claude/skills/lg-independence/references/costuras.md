# LG Independence — costuras

## Mapa núcleo / bordes
| Pieza | ¿Núcleo (poseer) o borde (alquilar)? | ¿Detrás de interfaz propia? |
|---|---|---|
| Lógica de decisión/orquestación | núcleo | n/a |
| UI del panel (React/Vite) | borde | ¿? |
| Persistencia (tablas WP/MySQL) | borde | ¿? |
| Inferencia / visión (ONNX) | borde | ¿? |
| Proveedor de lenguaje / APIs de tendencias | borde | ¿? |
| Auto-update / licencia | borde | ¿? |

## Prueba de reemplazo (por dependencia externa)
| Dependencia | ¿Cuánto duele cambiarla hoy? (bajo/medio/prohibitivo) | ¿Aparece en la lógica de negocio? | Acción de aislamiento |
|---|---|---|---|

Si "duele prohibitivo" o "aparece en la lógica" → aislar tras adaptador ahora.

## Independencia estratégica
- Proveedor crítico sin alternativa: ______ → segunda opción viable: ______
- Valor que vive en plataforma ajena: ______ → cómo diversificar/poseer: ______
- ¿Puedo exportar todos mis datos en formato abierto hoy? sí/no
