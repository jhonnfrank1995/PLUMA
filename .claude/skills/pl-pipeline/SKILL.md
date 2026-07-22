---
name: pl-pipeline
description: PLUMA — Máquina de estados de la Pieza, orquestador cron y contratos de eventos. Usar al crear o modificar cualquier transición de estado, el motor de ejecución, la cola de publicación, cuotas, ventanas, candados, reintentos, o al depurar Piezas estancadas o ejecuciones duplicadas.
---

# PLUMA — Pipeline y Orquestador

## Contratos innegociables
1. **Estados**: la Pieza solo transita por el grafo de `references/estados.md`. Toda transición pasa por `Pipeline\Transicionador::transitar(Pieza, EstadoPieza, string $motivo)` — jamás escribir el estado directo en el repositorio. El Transicionador valida el grafo, escribe auditoría y dispara `pluma/pieza_{estado}`.
2. **Idempotencia**: procesar dos veces el mismo lote no duplica nada. Toda operación del motor verifica estado actual antes de actuar y usa candado por Pieza además del global.
3. **Candado global**: TTL + liberación en `finally`. Segunda ejecución simultánea: sale en silencio y lo registra en bitácora.
4. **Presupuestos**: tiempo por ejecución y coste diario de API se verifican ANTES de cada operación cara. Al agotar: corte limpio entre lotes, jamás a mitad de una Pieza.
5. **Escasez honesta**: no existe ruta que publique bajo umbral para cumplir cuota. El déficit se registra y notifica (GOVERNANCE §2.7).
6. **Azar**: jitter y desempates solo vía `AzarInterface` con semilla inyectable en tests.
7. **Perecibilidad**: las Piezas de tendencias relámpago llevan caducidad; expirada → DESCARTADA con motivo, jamás publicada tarde.

## Al añadir un estado o transición
Actualizar: enum + grafo del Transicionador + `references/estados.md` + test del grafo (las transiciones ilegales lanzan) + pantalla de la Mesa Editorial + este skill. Los seis o ninguno.

## Referencias
- `references/estados.md` — grafo completo con motivos de transición y estados terminales.
