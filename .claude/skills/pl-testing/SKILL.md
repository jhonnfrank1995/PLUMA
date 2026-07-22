---
name: pl-testing
description: PLUMA — Mapa de tests y convenciones. Usar al escribir cualquier test, montar wp-env, crear dobles de proveedores, testear el orquestador (tiempo, azar, candados) o la suite de invariantes editoriales.
---

# PLUMA — Testing

## Mapa
- **Unit (PHPUnit + Brain\Monkey)**: lógica pura — puntuaciones del Radar, matrices, reconciliación taxonómica, Transicionador, clamps, n-gramas. Sin WP cargado.
- **Integración (wp-env)**: repositorios `pluma_*`, REST con capacidades, eventos, dbDelta idempotente, convivencia Yoast/Rank Math.
- **Invariantes (`tests/Invariantes/`)**: GOVERNANCE §2 completo — compuertas inevitables, degradación por sensibilidad, difamación→RETENIDA, anti-alucinación, escasez honesta, corpus anti-inyección. Corre en `/qa` y en `/auditoria-invariantes`. **Esta suite es el contrato del producto: jamás se debilita un assert para poner en verde una feature.**
- **E2E (Playwright)**: onboarding de 5 actos, ciclo completo de Pieza en Piloto con proveedor doble, veto Copiloto (desktop + móvil), estudio de conducta con vista previa.
- **JS (Vitest)**: lógica pura del panel.

## Convenciones
1. Tiempo y azar inyectables (`RelojInterface`, `AzarInterface`) — cero `time()`/`random_*` directos en `src/`.
2. Proveedores: solo dobles con fixtures (GOVERNANCE §4.4). Fixture nuevo = respuesta real anonimizada + fecha de captura.
3. Orquestador: probar siempre {doble ejecución, muerte a mitad de lote, API caída, presupuesto agotado} — el sub-agente ORQUESTADOR lo exige.
4. Regresión: todo bug → test con número de ticket antes del fix.
5. Los E2E asertan sobre RESULTADO real (post creado con schema, estados en BD, texto del bloque del editor), jamás solo "no hubo error".
