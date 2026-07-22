# Contrato de Lenguaje — referencia

**PeticionLenguaje** (final readonly): proposito (enum: clasificar|angulos|redactar|corregir|titulares|bloque_editor) · directrices (sistema + estilo del periodista, con versión) · material (expediente delimitado como DATOS) · limites (tokens, temperatura por propósito) · presupuesto_restante.
**RespuestaLenguaje** (final readonly): contenido · uso (tokens, coste) · proveedor · latencia_ms · truncada (bool).

Reglas: `truncada=true` → reintento con límites ajustados o FALLIDA — jamás se usa contenido truncado. Matriz de enrutamiento: propósito → modelo, editable en configuración, con piso de calidad inamovible para `redactar`.
