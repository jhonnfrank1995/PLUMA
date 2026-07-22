---
name: pl-proveedor-ia
description: PLUMA — Proveedores externos (lenguaje, tendencias, búsqueda). Usar al tocar Pluma\Proveedores, el armado de peticiones al modelo, la neutralización anti-inyección, presupuestos de coste, reintentos, circuit breakers o los fixtures de test de proveedores.
---

# PLUMA — Proveedores de IA y Datos

## Contratos innegociables
1. **Un contrato, N proveedores**: la lógica editorial habla con `LenguajeInterface` / `SensorInterface`; cambiar de proveedor no toca `Pluma\Redaccion`. Prohibido importar el SDK de un proveedor fuera de `Pluma\Proveedores`.
2. **Anti-inyección (GOVERNANCE §3.4)**: el armado de peticiones separa instrucciones del sistema y material del expediente con delimitación estricta; el material jamás se interpreta como órdenes; corpus adversarial en la suite (extractos que contienen "ignora tus instrucciones…" NO alteran la conducta).
3. **Coste primero**: presupuesto diario verificado ANTES de cada llamada; contadores por Pieza y por día en bitácora; enrutamiento por tarea (clasificar barato, redactar premium) declarado en configuración, no hardcodeado.
4. **Resiliencia**: timeout explícito, reintentos con retroceso exponencial + jitter, circuit breaker por proveedor (N fallos → abrir, sonda de recuperación), degradación declarada (sin tendencias, el Radar sigue con RSS; sin lenguaje, el motor pausa generación y notifica — jamás publica a medias). Aplican los patrones del ecosistema `http-client-resilience` y `exception-handling-and-logging`.
5. **Tests sin red**: fixtures de respuestas reales anonimizadas y contract tests por proveedor; la suite jamás llama APIs reales (GOVERNANCE §4.4).
6. **Secretos**: llaves cifradas, jamás en logs, en respuestas REST ni en exportes de diagnóstico.

## Referencias
- `references/contrato-lenguaje.md` — firmas de PeticionLenguaje/RespuestaLenguaje, política de delimitación del material y matriz de enrutamiento por coste.
