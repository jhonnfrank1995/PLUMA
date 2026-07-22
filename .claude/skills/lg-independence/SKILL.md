---
name: lg-independence
description: LG Labs — Independencia Tecnológica y Modularidad. Diseñar para no quedar atado a ninguna tecnología, proveedor o plataforma; límites modulares que aíslan el cambio. Usar al elegir dependencias, integrar servicios externos, definir fronteras entre módulos o cuando una pieza de terceros empieza a filtrarse por todo el sistema.
---

# LG Independence

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Un producto de clase mundial posee su núcleo y alquila sus bordes. Tu trabajo es que ninguna tecnología, proveedor o plataforma externa pueda tomar el sistema como rehén — ni técnica, ni comercial, ni estratégicamente.

## Principio rector: posee tu núcleo, alquila tus bordes

- **Núcleo** = la lógica que te hace único (decisión, orquestación, know-how, modelo del dominio). Se construye y se posee. Independiente de cualquier framework.
- **Bordes** = lo reemplazable (UI toolkit, base de datos, proveedor de inferencia, transporte, plataforma). Se alquila, y siempre detrás de una interfaz propia.

Regla: la lógica de negocio nunca importa directamente un SDK de terceros. Habla con **tu** interfaz; un adaptador delgado traduce al proveedor. El proveedor es intercambiable con cirugía local.

## Disciplina modular

1. **Fronteras en las junturas naturales**: los módulos se cortan por donde el problema tiene sus articulaciones, no por conveniencia técnica. Un cambio típico debe tocar un solo módulo.
2. **Dependencias apuntan hacia adentro**: el núcleo no depende de los bordes; los bordes dependen del núcleo (inversión de dependencias). El framework es un detalle, no el centro.
3. **Contrato explícito por frontera**: cada módulo expone qué promete y oculta cómo lo cumple. Si dos módulos conocen las tripas del otro, no hay frontera, hay un nudo.
4. **Prueba de reemplazo**: por cada dependencia externa, responde "¿cuánto duele cambiarla?". Si la respuesta es "hay que tocar medio sistema", la independencia ya se perdió — aíslala ahora.

## Independencia más allá del código

- **De proveedor**: ¿un cambio de precios, términos o disponibilidad nos deja sin salida? Ten siempre una segunda opción viable, aunque no la uses.
- **De plataforma**: ¿qué parte del valor vive en una plataforma que no controlamos (un buscador, una tienda de plugins, una API de tendencias)? Ese valor es prestado; diversifica o conviértelo en propio.
- **De datos**: los datos son tuyos solo si puedes exportarlos y llevártelos. Formato abierto y export desde el día uno (ver lg-future-thinking).

## Reglas

- Prohibido que un SDK de terceros aparezca en la lógica de negocio: siempre detrás de un adaptador propio.
- Independencia no es reinventar todo: usa librerías buenas, pero aisladas. La independencia vive en la *costura*, no en el orgullo de escribirlo tú.
- Toda dependencia de núcleo declara su plan de salida (ver lg-future-thinking y lg-risk-radar).
- Un solo proveedor crítico sin alternativa es un riesgo estratégico, no una decisión técnica — regístralo.

## Combina con

Materializa la modularidad que exige [LG Elegance](../lg-elegance/SKILL.md) y la trayectoria de [LG Future Thinking](../lg-future-thinking/SKILL.md). El riesgo de lock-in se gestiona con [LG Risk Radar](../lg-risk-radar/SKILL.md); la decisión de proveedor, con [LG Decision Framework](../lg-decision-framework/SKILL.md).

## Referencias

- [references/costuras.md](references/costuras.md) — mapa núcleo/bordes + prueba de reemplazo por dependencia.

## Dirección en PLUMA Engine

Núcleo que se posee: decisión editorial, memoria, compuertas, grafo de estados — jamás dependen de un SDK. Bordes que se alquilan tras interfaz propia: proveedor de lenguaje (`LenguajeInterface`), sensores de tendencias, WordPress mismo (el dominio no importa funciones WP fuera de sus adaptadores: si mañana existe "PLUMA para otra plataforma", el núcleo viaja). Prueba de reemplazo obligatoria y documentada para el proveedor de lenguaje (segunda opción viable configurada aunque no se use). Independencia del CLIENTE como argumento de venta: su banco de periodistas y su memoria se exportan en formato abierto — sus datos son suyos.
