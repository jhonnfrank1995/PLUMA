# Hooks públicos de PLUMA Engine

Convención: `pluma/` + snake_case (GOVERNANCE §1.4). Estabilidad declarada por
hook: **interno** (puede cambiar de firma sin aviso, aún no lo consume nadie
fuera del núcleo) o **público** (API de venta — romperlo es breaking change
SemVer, pl-wp-core §4).

## Eventos de transición de la Pieza

Disparados por `Pluma\Pipeline\Transicionador::transitar()` en toda
transición de estado aplicada (CLAUDE.md § Ley de Arquitectura: "toda
transición de estado dispara evento `pluma/pieza_{estado}`").

| Hook | Firma | Estabilidad | Desde |
|---|---|---|---|
| `pluma/pieza_en_investigacion` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_investigada` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_en_redaccion` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_redactada` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_fallida` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_descartada` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |
| `pluma/pieza_retenida` | `(int $piezaId, EstadoPieza $estadoAnterior, string $motivo)` | Interno | Etapa 1 |

Los estados posteriores (`optimizada`, `en_revision`, `aprobada`,
`programada`, `publicada`) existen en el grafo (`pl-pipeline`,
`references/estados.md`) pero ningún código los alcanza todavía — sus hooks
se documentan cuando el Motor SEO / Compuertas / Publicador (Etapas 3+) los
disparen por primera vez.

**Promoción a público**: cuando un módulo fuera del núcleo (newsletter,
redes sociales, analítica — Libro Cap. 2.3) consuma un hook, se promueve
aquí a "Público" y su firma queda congelada hasta la siguiente major.
