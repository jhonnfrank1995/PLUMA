# Grafo de Estados de la Pieza

DETECTADA → EN_INVESTIGACION → INVESTIGADA → EN_REDACCION → REDACTADA → OPTIMIZADA → EN_REVISION → APROBADA → PROGRAMADA → PUBLICADA

Salidas laterales (desde cualquier estado no terminal, siempre con motivo en auditoría):
- RETENIDA (compuertas o Corrector tras 2 ciclos) → reanudable por humano a EN_REVISION, o DESCARTADA.
- DESCARTADA (baja puntuación, caducidad, veto humano) — terminal.
- FALLIDA (error técnico, contador de reintentos; 3 fallos → alerta) → reanudable al estado previo.

Terminales: PUBLICADA, DESCARTADA. PUBLICADA admite el flujo lateral de corrección (banner + fecha de modificación), nunca retrocede en el grafo.
Regla: toda transición registra {de, a, actor (sistema|usuario), motivo, timestamp} en `pluma_auditoria`.
