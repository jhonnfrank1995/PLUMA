---
name: lg-risk-radar
description: LG Labs — Radar de Riesgos (Pre-Mortem). Detectar y gestionar riesgos ANTES de construir, de forma proactiva y continua. Usar antes de comprometerse a implementar algo no trivial, al iniciar un módulo/feature, y para mantener un registro vivo de riesgos del proyecto. Complementa (no sustituye) a la crítica de propuestas terminadas.
---

# LG Risk Radar

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

`lg-critical-review` ataca una propuesta *ya terminada*. Este skill es la disciplina *anticipatoria*: identifica cómo algo puede fallar **antes de invertir en construirlo**, y mantiene el riesgo bajo vigilancia durante toda la vida del proyecto.

## Técnica central — Pre-Mortem (retrospectiva prospectiva)

Antes de construir, imagina que ya pasó un año y **esto fracasó de forma espectacular**. Escribe la historia del fracaso:
- ¿Qué salió mal exactamente?
- ¿Cuál fue la causa raíz que hoy parece invisible?
- ¿Qué señal temprana ignoramos?

La retrospectiva prospectiva desbloquea riesgos que el optimismo del "esto va a funcionar" oculta.

## Dimensiones de riesgo a barrer

1. **Técnico**: puntos únicos de fallo, concurrencia (dos ejecuciones a la vez), datos corruptos, límites de recursos.
2. **De dependencia/plataforma**: la API cambia, limita o bloquea; el proveedor desaparece o cambia precios; la política de la plataforma cambia. Para un producto de publicación autónoma, el riesgo de penalización del buscador es de primera clase.
3. **De seguridad**: superficie de ataque, secretos expuestos, canal de update inseguro, integridad de licencia.
4. **Operacional**: qué pasa sin operador, con configuración errónea, en un entorno distinto al de desarrollo.
5. **De negocio/legal**: términos de servicio de las plataformas, cumplimiento, dependencia de un único canal.
6. **De ejecución**: la estimación más optimista del plan — triplícala; ¿sobrevive?

## Gestión (no basta con listar)

Para cada riesgo material: **probabilidad × impacto → respuesta**:
- *Evitar* (rediseñar para que no exista), *Mitigar* (reducir prob. o impacto), *Transferir*, o *Aceptar* (consciente y registrado).
- Define la **señal temprana** que indica que el riesgo se está materializando, y el **plan de contingencia**.
- Mantén un **registro vivo**: los riesgos no se cierran al lanzar; se revisan.

## Reglas

- Prohibido comprometerse a construir algo no trivial sin un pre-mortem de tres líneas como mínimo.
- Un riesgo sin dueño, señal y respuesta no está gestionado — solo anotado.
- Distingue riesgo (probabilístico, gestionable) de incertidumbre (desconocido): la incertidumbre se reduce con un experimento barato antes de la apuesta grande.
- El riesgo esencial del producto se diseña desde el día uno, no se parchea después. En PLUMA: penalización de Google por contenido escalado y riesgo legal por difamación — las compuertas SON la respuesta de diseño.

## Combina con

Anticipa lo que [LG Critical Review](../lg-critical-review/SKILL.md) confirmaría después. Alimenta las condiciones de ruptura de [LG Decision Framework](../lg-decision-framework/SKILL.md) y la homeostasis de [LG CTO Mode](../lg-cto-mode/SKILL.md).

## Referencias

- [references/registro-riesgos.md](references/registro-riesgos.md) — plantilla de pre-mortem + registro vivo de riesgos.

## Dirección en PLUMA Engine

Registro vivo inicial (revisar al abrir cada Etapa): penalización de Google (señal temprana: caída de indexación <48h, impresiones sin clics; respuesta: compuerta de originalidad + auditoría de contenido) · difamación (señal: RETENIDAS de riesgo en aumento; respuesta: doble fuente + revisión humana) · coste de tokens descontrolado (señal: 80% de presupuesto; respuesta: pausa de generación) · inyección de prompts vía fuentes (respuesta: corpus adversarial en la suite) · cambio/cierre de APIs de tendencias (respuesta: sensores enchufables + degradación a RSS) · hosting del cliente sin cron real (respuesta: detección en onboarding + guía) · compuertas que nunca muerden (señal: retención 0% una semana; respuesta: alerta y calibración). Pre-mortem de tres líneas obligatorio por Etapa del PLAN-MAESTRO.
