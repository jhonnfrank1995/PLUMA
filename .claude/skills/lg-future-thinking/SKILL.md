---
name: lg-future-thinking
description: LG Labs — Pensamiento a Futuro. Planificar con horizonte de 5–10 años, no solo el MVP. Integra reversibilidad, evolución de la arquitectura y radar de tecnologías emergentes. Usar en toda decisión estructural — modelo de datos, contratos, dependencias, formatos, elección de stack — que sea cara de revertir.
---

# LG Future Thinking

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Una decisión estructural no es una foto, es una película de 5–10 años. Tres frentes: horizonte, evolución y tecnología emergente.

## Frente 1 — Horizonte y reversibilidad

Clasifica primero:
- **Puerta de dos direcciones** (reversible barato): decide rápido, optimiza para velocidad, no gastes análisis.
- **Puerta de una dirección** (formato de datos, contrato público, dependencia estructural): aquí va todo el rigor.

Proyecta cada decisión de una dirección a **1 / 3 / 10 años**: ¿sobrevive al crecimiento? ¿a un equipo nuevo que no estuvo en esta conversación? ¿a que la plataforma/API/regulación de hoy haya muerto?

Máxima inercia (máximo cuidado): esquemas y formatos de persistencia (los datos viven más que el código), contratos públicos, dependencias de terceros en el núcleo.

## Frente 2 — Evolución de la arquitectura

Toda arquitectura trae su trayectoria:
- **Etapas** (hoy / crecimiento / madurez): qué cambia entre ellas y qué disparador (métrica) marca el salto. Evolucionar antes es sobreingeniería; después, crisis.
- **Costuras**: dónde se cortará el sistema al crecer (qué módulo se extrae, qué store se particiona). Se diseñan hoy aunque se usen en años.
- **Datos**: versionado de esquema y compatibilidad hacia atrás desde el día uno.
- No construyas hoy la arquitectura de la etapa 3: construye la etapa 1 con las costuras de la 2.

## Frente 3 — Radar de tecnología emergente

No elijas solo del catálogo de hoy:
- Para el problema, ¿qué enfoques emergen (modelos nuevos, APIs, betas)? Con conexión, verifica el estado del arte antes de afirmar que no hay algo mejor.
- Clasifica por madurez: *disponible hoy* (úsalo si gana) / *beta 6–18 meses* (diseña la costura para adoptarlo sin reescribir) / *horizonte 2–5 años* (no lo esperes, pero no lo bloquees).
- Detecta declive: ¿la tecnología que voy a usar está muriendo (mantenimiento, comunidad, camino oficial alternativo)?
- Aísla toda tecnología central tras una interfaz propia, para sustituirla con cirugía local.

## Reglas

- Toda decisión "temporal" recibe fecha o condición de revisión; lo temporal sin fecha es permanente.
- El MVP puede ser mínimo en features, nunca en formato de datos: migrar features es barato, migrar datos es caro.
- Prefiere lo aburrido y probado en cimientos; lo experimental solo en las hojas del sistema.

## Combina con

Extiende el horizonte de [LG CTO Mode](../lg-cto-mode/SKILL.md) y alimenta las alternativas de [LG Innovation Engine](../lg-innovation-engine/SKILL.md). La decisión final se formaliza en [LG Decision Framework](../lg-decision-framework/SKILL.md).

## Referencias

- [references/trayectoria.md](references/trayectoria.md) — horizontes + trayectoria de arquitectura + radar tecnológico.

## Dirección en PLUMA Engine

Puertas de una dirección declaradas (rigor completo + registro): el esquema `pluma_*`, el formato de export/import del Banco de Periodistas, los hooks públicos `pluma/` (API de venta, SemVer), el contrato `LenguajeInterface` y el formato del expediente. Radar: los modelos de lenguaje cambian cada pocos meses — la costura del proveedor existe para adoptarlos sin reescribir; vigilar declive de APIs de tendencias y cambios en políticas de Google (señal de revisión trimestral). El MVP puede ser mínimo en pantallas, jamás en el formato del expediente ni de la memoria: esos datos vivirán más que el código.
