# LG Critical Review — demolición

## Modos de fallo típicos
- **Carga**: picos, crecimiento sostenido, datos que no caben en memoria
- **Datos**: corruptos, duplicados, encoding raro, vacíos, maliciosos
- **Externos**: API cambia contrato, rate limits nuevos, política de plataforma endurecida, servicio desaparece
- **Humanos**: uso erróneo, configuración incorrecta, operador ausente
- **Tiempo**: zonas horarias, tareas solapadas, estado obsoleto
- **Concurrencia**: dos instancias a la vez, reintentos duplicando efectos

## Líneas de ataque por tipo
- **Arquitectura**: ¿punto único de fallo? ¿100x o 0 datos? ¿qué dependencia la mata?
- **Plan**: triplica la estimación más optimista, ¿sobrevive? ¿y si el paso 1 revela falsa la premisa del paso 3?
- **Tecnología**: ¿se eligió por mérito o por familiaridad? ¿su caso notoriamente malo nos toca?
- **Producto**: ¿quién NO lo usaría y por qué? ¿qué haría el competidor más agresivo al vernos lanzar?
- **Marco**: ¿y si el problema declarado es un síntoma? ¿qué sería posible sin la restricción que todos asumen?

## Diseño de la salida
- Si sale mal, ¿qué se migra (datos, integraciones, hábito)?
- Coste de reversión: bajo / medio / prohibitivo → si prohibitivo, doble escrutinio antes de entrar.
