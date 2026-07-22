# Registro de Deuda Técnica — PLUMA Engine
Regla (PLAN-MAESTRO): deuda sin etapa de pago asignada no existe — es omisión. Formato: | fecha | descripción | origen | etapa de pago | ticket |

| Fecha | Deuda | Origen | Pago | Ticket |
|---|---|---|---|---|
| 2026-07-22 | `tests/Integration/CicloDeVidaTest.php` no cubre activación de red completa en multisitio real (solo cubierto vía dobles en `ActivadorTest` Unit); requiere `.wp-env.json` en modo multisitio, no configurado en Etapa 0 | Etapa 0 — Cimientos | Etapa 1 (cuando el orquestador y las tablas `pluma_*` añadan superficie real que justifique el entorno multisitio) | PLUMA-E0-1 |
