---
name: lg-design-excellence
description: LG Labs — Excelencia en Diseño. Filosofía de experiencia de usuario e identidad visual coherente. Usar al crear o revisar pantallas, componentes, flujos, estilos (QSS/temas), iconografía o cualquier decisión de interfaz — cada elemento justifica su existencia antes de discutir su apariencia, y toda apariencia obedece a un sistema.
---

# LG Design Excellence

> **Edición PLUMA Engine v2.0** (2026-07-22) — núcleo LG Labs canónico + dirección de proyecto. Cambios locales: sección «Dirección en PLUMA Engine» y ejemplos generalizados.

Dos capas: primero **por qué existe** cada elemento (filosofía), luego **cómo se ve** de forma coherente (identidad visual). Nunca al revés.

## Capa 1 — Filosofía (antes de cualquier pixel)

1. **¿Por qué existe esta pantalla?** ¿Qué decisión o acción del usuario habilita? Si solo "muestra información", ¿por qué necesita verla en vez de que el sistema actúe?
2. **¿Por qué existe este botón?** Cada botón es una decisión que delegamos al usuario; ¿es justo delegársela o puede el sistema resolverla?
3. **¿Por qué existe este flujo?** ¿Cuántos pasos son esenciales y cuántos son deuda de implementación disfrazada de UX?

Principios: la mejor interfaz es la que no existe (automatiza → muestra → pregunta solo lo irreducible). Cada elemento visible compite por atención. La configuración es una derrota del diseño: cada opción es una decisión que no supimos tomar. El estado vacío y el de error son diseño, no casos borde.

## Capa 2 — Identidad visual (sistema, no decoración)

La apariencia obedece a un sistema con tokens, no a decisiones ad-hoc por pantalla:
- **Tokens**: color, tipografía, espaciado, radios, sombras, elevación — definidos una vez, reutilizados en todas partes. Un color nuevo por pantalla es un bug de identidad.
- **Jerarquía**: tamaño, peso y contraste comunican importancia antes que las palabras. Un solo acento primario por vista.
- **Consistencia**: mismo patrón visual = mismo significado en todo el producto. El usuario aprende una vez.
- **Marca**: la identidad (LG) debe sentirse sin gritar — coherente, no ruidosa. Personalidad en los detalles (microcopys, estados, transiciones), no en adornos.
- **Accesibilidad**: contraste suficiente, foco visible, objetivos táctiles amplios, no depender solo del color. No es opcional.
- **Estados completos**: cada componente diseña sus estados vacío / cargando / error / lleno / deshabilitado.

## Reglas

- Prohibido discutir color o layout de una pantalla antes de escribir en una frase el trabajo que el usuario viene a hacer ahí.
- Todo estilo nuevo se pregunta primero: ¿existe ya un token para esto? Si lo inventas, promuévelo al sistema, no lo dejes suelto.
- Añadir un elemento tiene coste sobre todos los demás; justifícalo o elimínalo.

## Combina con

Materializa [LG Product Vision](../lg-product-vision/SKILL.md). Somételo a [LG Critical Review](../lg-critical-review/SKILL.md) (¿esta pantalla sobrevive al ataque?).

## Referencias

- [references/checklist.md](references/checklist.md) — checklist de existencia (filosofía) + auditoría de sistema visual.

## Dirección en PLUMA Engine

La filosofía del cap. 10 del Libro manda: metáfora de sala de redacción, no de configuración. Cada pantalla declara primero qué decisión habilita (la Sala de Revisión habilita UNA: aprobar/vetar — y por eso se diseña para el móvil primero). "La configuración es una derrota": los diales del periodista son producto (configuración deseada), pero cada opción técnica nueva del motor es una decisión que no supimos tomar — default de fábrica primero. Los estados vacío/cargando/error del pipeline son diarios, no bordes: un déficit de cuota o una Pieza RETENIDA deben verse dignos y accionables.
