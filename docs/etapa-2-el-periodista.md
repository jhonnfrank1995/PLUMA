# Etapa 2 — El periodista

**Estado:** Cerrada el 2026-07-23 · CI en verde en GitHub Actions (run [29981885310](https://github.com/jhonnfrank1995/PLUMA/actions/runs/29981885310)).

## Objetivo y criterio de salida (PLAN-MAESTRO)

> Banco con diales, decisión editorial, redacción dos pasadas + Corrector, memoria, bloque del editor.
> **Criterio de salida**: dos periodistas distinguibles a ciegas; invariantes GOVERNANCE §2 en verde; export/import del banco.

## Qué se agregó

- **`Pluma\Proveedores`**: contrato del Proveedor de Lenguaje (`LenguajeInterface`) + `ProveedorOpenRouter` (acceso a cualquier modelo de cualquier IA vía OpenRouter), `PropositoLenguaje` (enrutamiento por coste: clasificar con modelo económico, redactar con el mejor), `NeutralizadorMaterial` (anti-inyección de prompts con centinela aleatorio + saneado — todo el material del expediente entra al proveedor como datos, nunca como instrucciones), `PresupuestoLenguaje` (verificado ANTES de cada llamada, aviso al 80% del límite diario), `EnrutadorModelos`.
- **`Pluma\Kernel\Cifrado`**: llaves de API cifradas en reposo con libsodium, con clave derivada de las salts de `wp-config.php` — nunca en texto plano en `wp_options`.
- **Esquema `0.3.0`**: tablas `pluma_periodistas`, `pluma_periodistas_conducta_versiones` (Conducta versionada e inmutable, nunca se sobrescribe), `pluma_memoria_editorial`, `pluma_borradores`; `pluma_piezas` ampliada con `periodista_id`, `periodista_version_id`, `ficha_decision_editorial`.
- **`Pluma\Redaccion`**: el modelo completo del periodista sintético — Identidad, Diales (temperamento 0–100), Reglas de Conducta (cualitativas), Matriz de Tonos con **bloqueo de sátira en Tragedia como regla de sistema inviolable** (ninguna configuración de usuario la anula); `CompiladorDirectrices`; `PlantillasSiembra` (banco inicial recomendado: analista de datos, columnista crítica, cronista satírico).
- **El Algoritmo de Decisión Editorial** (Libro Cap. 5.5, 4 pasos): `ClasificadorNoticia`, `AsignadorPeriodista`, `SelectorAngulo` (con "memoria antes de tesis": las posturas previas del periodista se consultan antes de elegir ángulo), `GeneradorEsqueleto` — producen la Ficha de Decisión Editorial completa de cada Pieza.
- **`RedactorSintetico` + `CorrectorInterno`** (lista de verificación de 6 puntos: hechos, proporción interpretativa, solapamiento n-grama, voz, titular honesto, matriz/líneas rojas) + `VerificadorVoz`/`VerificadorNGramas` (deterministas, no dependen del proveedor de lenguaje) + `GeneradorBloqueEditor`. Máximo 2 ciclos de revisión; al tercero, la Pieza se marca RETENIDA — **nunca se publica "lo menos malo"**.
- **`AvisoTransparenciaIa`** y cita/enlace mecánico de toda fuente usada, en cada pieza redactada (GOVERNANCE §2.5/§2.6).
- **`RedactorConFallbackMecanico`**: si el proveedor de lenguaje no tiene presupuesto o credenciales, notifica (`pluma/redactor_fallback_mecanico`) y usa el redactor mecánico heredado de la Etapa 1 — decisión explícita del propietario, nunca bloquea el pipeline.
- **`Pluma\Admin\RestBancoPeriodistas`**: export/import completo del banco de periodistas y su memoria (`GET`/`POST /pluma/v1/periodistas/{exportar,importar}`), protegido con la capacidad `pluma_gestionar_periodistas`.
- **`tests/Invariantes`**: nace aquí como suite dedicada a GOVERNANCE §2 (anti-alucinación, bloqueo de sátira, cita/enlace de fuentes, transparencia de autoría IA) — criterio de salida explícito de la Etapa 2 en PLAN-MAESTRO.

## Qué se corrigió / decisiones no triviales

Esta etapa se construyó antes del inicio de esta sesión — no hay historial de depuración interno disponible más allá de lo que consta en `CHANGELOG.md`/`docs/deuda.md`. La decisión de alcance más relevante, explícita y aprobada por el propietario: **el fallback mecánico ante falta de presupuesto/credenciales no se mejoró en esta etapa** ("notificar y usar el redactor mecánico; en el futuro lo mejoramos a nivel máximo") — quedó registrada como deuda en vez de resolverse a medias.

## Deuda técnica de esta etapa

| Ticket | Deuda | Pago asignado |
|---|---|---|
| PLUMA-E2-1 | `RedactorMecanico` (fallback de `RedactorConFallbackMecanico`) sigue siendo el redactor mecánico rudimentario de la Etapa 1: sin periodista, sin diales, sin Corrector Interno | Etapa futura sin asignar todavía — **sigue abierta** |
| PLUMA-E2-2 | `AsignadorPeriodista::afinidadLineaEditorial()` usa un heurístico léxico simple (solapamiento de palabras ≥4 letras), no comprensión semántica real | Etapa 4/5 (cuando presupuesto/telemetría de audiencia justifiquen una puntuación semántica real) — **sigue abierta**; ninguna porción de la Etapa 4 entregada hasta ahora (Portada, Sala de Tendencias, Mesa Editorial) la tocó |
| PLUMA-E2-3 | El "compromiso de respuesta" del Bloque del Editor (borradores de respuesta a comentarios reales de WordPress) no está construido — `GeneradorBloqueEditor` solo produce comentario + pregunta | Etapa 5 (explícitamente listado en el criterio de esa etapa en PLAN-MAESTRO) — **sin tocar, en su etapa correcta** |
| PLUMA-E2-4 | No existe pantalla de administración ni endpoint REST para introducir/cifrar la llave de OpenRouter (`ProveedorOpenRouter::OPCION_LLAVE_CIFRADA` solo se puebla hoy vía `wp option update`/`Cifrado::cifrar()` manual) — sin esto, `RedactorSintetico` nunca tiene credenciales reales en una instalación de cliente y el fallback mecánico se activa siempre | Etapa 4 (panel completo, Cap. 10, es donde nace el resto de la configuración del motor vía UI) — **⚠️ sigue abierta al cierre de la porción 3 de la Etapa 4**; ninguna de las tres porciones entregadas hasta ahora (Portada, Sala de Tendencias, Mesa Editorial) construyó esta pantalla — es candidata natural para la porción de "Sala de Máquinas" o el onboarding (acto 2: "conexión de llaves de APIs con prueba en vivo") |

## A tener en cuenta para otras fases

- **PLUMA-E2-4 es la deuda más urgente pendiente de la Etapa 4**: sin una pantalla real para cargar la llave de OpenRouter, cualquier instalación de cliente nueva corre siempre en modo fallback mecánico — el "wow" del onboarding (Cap. 10.3: "el sistema ejecuta su primer ciclo en vivo... produce su primer borrador") no puede demostrar redacción sintética real hasta que esto se resuelva.
- **El bloqueo de sátira en Tragedia fijado aquí como regla de sistema inviolable** es el mismo principio que la Etapa 3 tuvo que reforzar en una "segunda capa" dentro de Compuertas (invariante GOVERNANCE §2.2 segunda capa) — cualquier lógica nueva de generación de contenido debe respetar esta regla en las dos capas, no solo en `Pluma\Redaccion`.
- **El versionado inmutable de Conducta** (`pluma_periodistas_conducta_versiones`) es la base directa del Estudio de Conducta que falta construir en la Etapa 4 (Banco de Periodistas — la "pantalla estrella" del Cap. 10.2, con vista previa en vivo al mover un dial): cualquier cambio de diales desde el panel debe crear una versión nueva, nunca sobrescribir la actual.
- **`RedactorMecanico` de la Etapa 1 sigue siendo código vivo**, no legado a eliminar — es el fallback real de producción hasta que se resuelva PLUMA-E2-1.

## Evidencia de gates al cierre

Suite `tests/Invariantes` nace aquí (GOVERNANCE §2.4, §2.2 parcial, §2.5, §2.6) — los 4 jobs de CI (calidad PHP con `test:invariantes`, calidad JS, integración wp-env, empaquetado) en `success`.
