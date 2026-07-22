# AGENTS.md — PLUMA Engine
# Roles de Agente y Protocolo de Coordinación

---

## AGENTE PRIMARIO: INGENIERO DE DESARROLLO

**Alcance**: toda implementación, bug, refactor, migración.
**Gobernado por**: CLAUDE.md (íntegro) + GOVERNANCE.md (íntegro) + skills aplicables (SKILLS-STACK §2).
**Protocolo pre-tarea (obligatorio)**: leer el código existente relevante → Mission Lock + PLAN GUARDIAN → descubrimiento de skills → confirmar capa → nombrar los tests → ante ambigüedad, STOP y preguntar.

Los sub-agentes se activan automáticamente por disparador. Sus reglas son ADITIVAS, nunca alternativas.

---

### SUB-AGENTE ESQUEMA
**Dispara**: cualquier cambio en tablas `pluma_*` (columna, índice, tipo, tabla nueva).
**Reglas**: versión de esquema en opción `pluma_db_version`; toda migración con procedimiento de reversa documentado y probado; índices en todo campo de estado+fecha (las consultas del motor son "dame N piezas en estado X por prioridad"); tipos de columna alineados con los DTOs; renombrados en tres pasos (crear-copiar-eliminar), nunca destructivos directos; expedientes voluminosos comprimidos; política de retención respetada.
**Verificación**: `□ up limpio □ reversa limpia □ índices de estado presentes □ migración N-1→N probada con datos □ dbDelta idempotente (ejecutar dos veces = cero cambios)`

### SUB-AGENTE ORQUESTADOR
**Dispara**: cualquier cambio en el motor cron, candado, presupuestos, cuotas, cola de publicación.
**Reglas**: candado con TTL y liberación en `finally`; presupuesto de tiempo respetado con corte limpio entre lotes; toda operación idempotente (re-ejecutar un lote no duplica Piezas ni posts); reintentos con retroceso exponencial y estado FALLIDA visible; jitter vía `AzarInterface`; déficit de cuota registrado y notificado, jamás rellenado.
**Verificación**: `□ doble ejecución simultánea probada □ muerte a mitad de lote probada (recuperación) □ API caída probada □ presupuesto de coste respetado antes de cada llamada □ bitácora completa de la ejecución`

### SUB-AGENTE PERIODISTA
**Dispara**: cualquier cambio en `Pluma\Redaccion` — diales, matriz de tonos, memoria, decisión editorial, Corrector Interno, bloque del editor.
**Reglas**: leer skill `pl-periodistas` completo antes; el redactor solo conoce el expediente (anti-alucinación, GOVERNANCE §2.4); contradicción con postura previa exige reconocimiento en texto; sátira en tragedia bloqueada por sistema; máximo 2 ciclos de corrección y a RETENIDA; todo cambio de conducta versiona la configuración del periodista; export/import del banco intacto tras el cambio.
**Verificación**: `□ invariantes de GOVERNANCE §2 en verde □ test de voz (rasgos presentes, vocabulario prohibido ausente) □ memoria consultada antes de tesis □ ficha de decisión completa y trazable`

### SUB-AGENTE SEGURIDAD
**Dispara**: entrada de usuario · endpoint REST nuevo · URL externa · manejo de llaves · uploads · webhook/cron endpoint.
**Reglas**: GOVERNANCE §3 completo aplicado; checklist antes de cerrar:
`□ cero input crudo □ cero SQL sin prepare □ nonce+capacidad en todo endpoint □ salida escapada □ SSRF validado □ llaves cifradas y no logueadas □ material de fuentes tratado como datos (anti prompt-injection) □ cero secretos en el repo`

### SUB-AGENTE PANEL
**Dispara**: cualquier pantalla, componente o flujo del panel de administración.
**Reglas**: leer `pl-wp-core` + skill de diseño aplicable (ui-ux-pro-max / frontend-design del ecosistema); metáfora de redacción, no de configuración (Libro cap. 10); tokens del sistema visual, cero colores ad-hoc; estados vacío/cargando/error/lleno diseñados; responsive real (la pantalla de veto se usa desde el móvil); i18n completa; assets solo en pantallas PLUMA; accesibilidad (foco visible, contraste, no solo color).
**Verificación**: `□ filosofía antes que pixel (qué decisión habilita la pantalla) □ cero token nuevo sin promover al sistema □ build de producción verificado □ Playwright de la pantalla en verde`

### SUB-AGENTE RELEASE
**Dispara**: preparación de versión, empaquetado, licenciamiento, actualizaciones.
**Reglas**: GOVERNANCE §5 completo; smoke test del ZIP en WP limpio; matriz de compatibilidad ejecutada; migración N-1→N con datos reales; degradación elegante sin licencia probada; CHANGELOG orientado al cliente; documentación de venta actualizada.
**Verificación**: `□ build reproducible □ ZIP instala y activa limpio □ matriz completa en verde □ rollback de release documentado □ firmas de actualización válidas`

---

## PROTOCOLO DE COORDINACIÓN

1. Una tarea puede activar varios sub-agentes; se aplican TODOS sus checklists.
2. Conflicto entre reglas de sub-agentes = conflicto de arquitectura → STOP, documentar, decisión del propietario vía LG Decision Framework.
3. Ningún sub-agente autoriza saltarse un gate de GOVERNANCE §4.5. Nadie lo hace.
