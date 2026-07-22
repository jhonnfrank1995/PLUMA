# PLUMA ENGINE
## Libro de Arquitectura de un Plugin Editorial Autónomo para WordPress

*Documento de diseño y construcción — Versión 1.0*

---

# PRÓLOGO: QUÉ ES ESTE DOCUMENTO

Este no es el manual de un plugin que existe. Es el plano maestro del plugin que vas a construir: un sistema editorial autónomo que vive dentro de WordPress, detecta las noticias más buscadas del momento, las investiga con rigor multifuente, y las transforma en piezas editoriales 100% originales a través de un **periodista sintético** con identidad, conducta y criterio configurables. El sistema publica solo, según el volumen diario que tú definas, y cada pieza sale con sello editorial, capa SEO completa, taxonomía inteligente y llamado a la conversación.

El nombre de trabajo es **PLUMA Engine** (puedes rebautizarlo). Este libro cubre la visión, la arquitectura de módulos, el algoritmo del periodista sintético con sus parámetros de conducta, el motor de publicación programada, el diseño premium de la experiencia de administración, el modelo de datos, la seguridad, la gobernanza editorial y el plan de construcción por etapas.

Una advertencia de arquitecto antes de empezar: el enemigo mortal de este producto no es la competencia, es Google. Sus políticas contra el "scaled content abuse" desindexan sitios que publican contenido automatizado sin valor añadido. Por eso este diseño no automatiza "la escritura de noticias": automatiza **un criterio editorial**. La diferencia entre ambas cosas es la diferencia entre un imperio y una penalización manual. Todo el libro está escrito con esa distinción como principio rector.

---

# CAPÍTULO 1 — VISIÓN Y PRINCIPIOS DE DISEÑO

## 1.1 La tesis del producto

PLUMA no es un "auto-blogger" ni un "spinner" de artículos. Los auto-bloggers copian; los spinners disfrazan. PLUMA pertenece a una tercera categoría que casi no existe en el mercado: el **newsroom sintético**. Su unidad de trabajo no es "el artículo", es "la decisión editorial": qué cubrir, con qué ángulo, con qué voz, con qué postura, y qué conversación provocar.

## 1.2 Los siete principios de diseño

**P1 — El criterio es el producto.** La noticia es materia prima gratuita que todos tienen. Lo que el plugin fabrica es interpretación: análisis, crítica, opinión, humor, sátira. Toda decisión de arquitectura se evalúa contra esta pregunta: ¿esto añade criterio o solo añade volumen?

**P2 — Personas, no prompts.** El sistema no "genera texto con un tono". Mantiene periodistas sintéticos persistentes, con nombre, biografía, especialidad, historial de posturas y parámetros de conducta. Un lector que lea tres artículos del mismo periodista debe reconocer la voz.

**P3 — Originalidad estructural, no léxica.** Cambiar sinónimos es plagio con maquillaje. La originalidad se garantiza en la estructura: tesis propia, arquitectura de argumentos propia, datos cruzados de fuentes múltiples, y una proporción obligatoria (mínimo 60%) de contenido interpretativo sobre contenido factual.

**P4 — Autonomía con frenos.** El plugin puede publicar sin intervención humana, pero cada pieza pasa por compuertas de calidad y riesgo automáticas. La autonomía es un privilegio que el contenido se gana pieza por pieza, no un derecho por defecto.

**P5 — SEO como capa, nunca como esqueleto.** Primero se escribe la mejor pieza editorial posible; después se optimiza. Un artículo diseñado desde la keyword suena a artículo diseñado desde la keyword, y Google (y los lectores) lo notan.

**P6 — Trazabilidad total.** Cada artículo publicado debe poder responder: ¿de qué tendencia nació, qué fuentes consultó, qué periodista lo firmó, qué decisiones tomó el algoritmo y por qué? Sin trazabilidad no hay depuración, no hay mejora y no hay defensa legal.

**P7 — Experiencia de administración de nivel producto, no de nivel plugin.** El panel debe sentirse como la sala de redacción de un medio premium: tablero de tendencias, banco de periodistas, cola editorial, sala de revisión. Nada de formularios grises con veinte checkboxes.

## 1.3 A quién sirve

- **El editor-fundador (tú):** define la línea editorial, configura periodistas, fija el volumen diario y supervisa desde un tablero.
- **Los periodistas sintéticos:** ejecutan el trabajo de investigar, decidir ángulo, redactar, firmar y conversar.
- **El lector:** recibe piezas con voz, postura y una invitación genuina a opinar.
- **Google:** recibe señales de E-E-A-T — autores identificables, fuentes citadas, contenido con ganancia de información, sitio con autoridad temática.

## 1.4 Métricas de éxito del producto

| Métrica | Objetivo año 1 |
|---|---|
| Tráfico orgánico mensual | 100.000 – 400.000 sesiones |
| % de piezas indexadas en <48h | > 85% |
| Tiempo tendencia→publicación | < 90 minutos en modo autónomo |
| Tasa de piezas retenidas por compuertas de calidad | 10–20% (si es 0%, las compuertas no funcionan) |
| Comentarios por artículo (mediana) | > 3 al mes 6 |
| Penalizaciones manuales de Google | 0 (métrica de vida o muerte) |

---
# CAPÍTULO 2 — ARQUITECTURA GENERAL DEL SISTEMA

## 2.1 Vista de pájaro

PLUMA se organiza como una tubería (pipeline) de siete módulos desacoplados, orquestados por un motor de ejecución central. Cada módulo recibe un objeto de trabajo, lo enriquece y lo pasa al siguiente. Si un módulo falla, la pieza queda en un estado recuperable, nunca a medias en el blog.

```
┌─────────────────────────────────────────────────────────────────┐
│                      ORQUESTADOR (Motor Cron)                   │
│   "Cada vez que me ejecuto, cumplo mi cuota del día"            │
└──────┬──────────────────────────────────────────────────────────┘
       │
       ▼
 [1] RADAR ──▶ [2] INVESTIGADOR ──▶ [3] SALA DE REDACCIÓN ──▶
     detecta        recopila y           periodista sintético:
     tendencias     verifica fuentes     ángulo + redacción + firma
       │
       ▼
 [4] MOTOR SEO ──▶ [5] TAXÓNOMO ──▶ [6] COMPUERTAS ──▶ [7] PUBLICADOR
     optimiza        categorías y        calidad, riesgo,     programa y
     la capa         etiquetas           originalidad         publica en WP
```

## 2.2 El objeto de trabajo: la "Pieza"

Todo el sistema gira alrededor de una entidad llamada **Pieza** (Piece), que atraviesa estados como un artículo atraviesa una redacción real:

`DETECTADA → EN_INVESTIGACIÓN → INVESTIGADA → EN_REDACCIÓN → REDACTADA → OPTIMIZADA → EN_REVISIÓN → APROBADA → PROGRAMADA → PUBLICADA` (con salidas laterales: `RETENIDA` por compuertas, `DESCARTADA` por baja puntuación, `FALLIDA` con reintento).

La Pieza acumula todo su expediente: la tendencia origen, las fuentes con sus extractos y fechas, la ficha de decisión editorial (ángulo, tono, tesis), los borradores, la puntuación de compuertas, y el resultado final. Ese expediente es la trazabilidad del Principio P6.

## 2.3 Estilo arquitectónico dentro de WordPress

- **Núcleo orientado a servicios internos:** cada módulo es una clase de servicio con una interfaz estrecha (contrato), registrada en un contenedor de dependencias ligero. Nada de funciones sueltas globales: eso hace el plugin testeable y extensible.
- **Sistema de eventos propio sobre hooks de WP:** cada transición de estado de una Pieza dispara un evento (`pluma/pieza_investigada`, `pluma/pieza_publicada`...). Esto permite que módulos futuros (newsletter, redes sociales, analítica) se enganchen sin tocar el núcleo.
- **Tablas propias, no post-meta infinito:** las Piezas, fuentes, periodistas y colas viven en tablas dedicadas (Capítulo 10). El post de WordPress se crea solo al final; el 90% de la vida de una Pieza transcurre fuera de `wp_posts`, lo que mantiene la base del blog limpia.
- **Capa de IA como proveedor intercambiable:** el módulo de redacción habla con un "Proveedor de Lenguaje" abstracto. Detrás puede haber la API de Anthropic, otra API o un modelo local; cambiar de proveedor no debe tocar la lógica editorial.
- **Procesamiento asíncrono por lotes:** ninguna operación pesada (investigar, redactar) se ejecuta en una petición web del navegador. Todo corre en trabajos de fondo con bloqueo (locking) para que dos ejecuciones del cron no procesen la misma Pieza.

## 2.4 Los tres modos de operación (gobernanza integrada)

El plugin nace con tres modos, seleccionables globalmente y anulables por categoría o por periodista:

1. **Modo Piloto:** el sistema hace todo el trabajo pero deja las piezas como borradores. Es el modo de entrenamiento y calibración (primeras 2–4 semanas obligatorias).
2. **Modo Copiloto:** el sistema programa la publicación con una **ventana de veto** configurable (ej. 45 minutos). Recibes una notificación; si no intervienes, se publica. Es el equilibrio ideal entre velocidad y control.
3. **Modo Autónomo:** publicación directa sin intervención, pero con las compuertas del Capítulo 8 endurecidas: los temas sensibles (tragedias, salud, acusaciones a personas) se degradan automáticamente a Copiloto o Piloto aunque el modo global sea Autónomo.

Esta degradación automática por sensibilidad no es opcional en el diseño: es el seguro de vida legal y reputacional del producto.

---

# CAPÍTULO 3 — MÓDULO RADAR: DETECCIÓN DE TENDENCIAS

## 3.1 Misión

Responder cada hora la pregunta: *¿qué está buscando el mundo ahora mismo que intersecta con nuestra línea editorial y donde podemos ganar?* El Radar no busca "todo lo viral": busca la intersección de tres círculos — volumen de búsqueda creciente, afinidad con los verticales del sitio, y hueco competitivo (nadie lo ha cubierto con ángulo editorial todavía).

## 3.2 Fuentes de señal (agregación multi-canal)

- **Google Trends** (tendencias en tiempo real por país y categoría): la señal primaria de volumen.
- **Agregadores de noticias vía RSS/APIs** (Google News, portadas de medios de referencia del nicho): la señal de qué ya es noticia.
- **Señal social** (tendencias de X/Reddit vía sus APIs o servicios intermedios): la señal de conversación y de ángulo emocional.
- **Búsqueda interna del propio sitio y Search Console:** qué buscan tus propios lectores y en qué consultas ya apareces sin ganar el clic — oro puro para decidir cobertura.

Cada fuente se implementa como un "Sensor" enchufable con la misma interfaz: el Radar de mañana puede añadir sensores (YouTube, TikTok, podcasts) sin reescribir nada.

## 3.3 El algoritmo de puntuación de tendencias

Cada tendencia detectada recibe una **Puntuación de Oportunidad (0–100)** compuesta de cuatro factores ponderados (pesos configurables desde el panel):

| Factor | Peso sugerido | Qué mide |
|---|---|---|
| Velocidad | 35% | Aceleración del volumen de búsqueda (no el volumen absoluto: una tendencia que crece 400% en 2 horas vale más que una grande y plana) |
| Afinidad | 30% | Similitud semántica entre la tendencia y los verticales/etiquetas del sitio |
| Hueco competitivo | 20% | Cuántos resultados del SERP ya son piezas editoriales (no solo notas de agencia); menos cobertura interpretativa = más hueco |
| Vida útil estimada | 15% | Clasificación de la tendencia como relámpago (horas), ola (días) o marea (semanas); las olas y mareas puntúan más porque amortizan mejor el esfuerzo |

Las tendencias que superan el **umbral de cobertura** (configurable, ej. 60 puntos) entran a la cola editorial. Las que no, quedan registradas 72 horas por si aceleran.

## 3.4 Deduplicación y memoria

El Radar mantiene una huella semántica de cada tendencia procesada para no cubrir dos veces la misma historia con distinto titular, y para detectar el caso contrario y más valioso: una historia ya cubierta que **evoluciona** (nuevo dato, giro, desmentido), lo que dispara una Pieza de actualización enlazada a la original — la estrategia de "dos golpes" que Google News premia.

---

# CAPÍTULO 4 — MÓDULO INVESTIGADOR: RECOPILACIÓN MULTIFUENTE

## 4.1 Misión

Convertir una tendencia en un **expediente de investigación**: los hechos verificados, las cifras, las declaraciones textuales con atribución, el contexto histórico y los ángulos que la competencia no vio. La calidad de la pieza final se decide aquí, no en la redacción: un periodista brillante con un expediente pobre escribe una pieza pobre.

## 4.2 Protocolo de investigación

Para cada Pieza, el Investigador ejecuta un protocolo de cinco pasos:

1. **Localizar la fuente primaria.** El comunicado, el documento oficial, la declaración original, el estudio. Si existe, se cita a ella, no al medio que la contó.
2. **Recolectar 4–8 coberturas secundarias** de medios diversos (idealmente con líneas editoriales distintas), extrayendo de cada una: los hechos que reporta, los que omite, y su encuadre.
3. **Triangular.** Los hechos afirmados por 2+ fuentes independientes se marcan como *verificados*; los de fuente única, como *atribuidos* (y la redacción deberá atribuirlos explícitamente: "según X medio..."); las contradicciones entre fuentes se marcan como *disputados* — y un hecho disputado es, en sí mismo, un ángulo editorial.
4. **Contextualizar.** Buscar el historial: ¿ya pasó algo parecido? ¿qué dijo este actor hace un año? ¿qué dicen los datos duros (cifras oficiales, series históricas)? El contexto es la materia prima del análisis.
5. **Detectar el hueco.** Comparar todos los encuadres recolectados y responder: ¿qué pregunta obvia nadie está haciendo? Ese hueco se anota en el expediente y será candidato a tesis de la pieza.

## 4.3 Higiene de fuentes

- **Lista de confianza por niveles:** fuentes primarias y medios de referencia (nivel A), medios generalistas (nivel B), agregadores y blogs (nivel C, solo como pista, nunca como sustento). La lista es editable desde el panel.
- **Lista negra:** dominios de desinformación conocidos, granjas de contenido y sitios que el editor vete. Nada de la lista negra entra jamás a un expediente.
- **Extractos limitados y con registro:** el expediente guarda citas breves con URL, fecha y autor — lo necesario para atribuir y enlazar, jamás para reproducir. El plugin enlaza a las fuentes en la pieza publicada: citar y enlazar no es solo ética, es señal de E-E-A-T.
- **Sello temporal de todo:** en noticias, un dato de hace 6 horas puede estar muerto. Cada hecho del expediente lleva su marca de tiempo y el Publicador re-verifica los hechos críticos si la pieza tardó más de N horas en salir.

---
# CAPÍTULO 5 — LA SALA DE REDACCIÓN: EL PERIODISTA SINTÉTICO

*Este es el corazón del producto y el capítulo más importante del libro. Cubre las fases editoriales 3 (ángulo), 4 (redacción) y 7 (sello y conversación) como un solo organismo: un periodista simulado con identidad, conducta y memoria.*

## 5.1 Filosofía: simular un periodista, no un generador

Un generador produce texto a partir de una orden. Un periodista tiene algo que el generador no tiene: **continuidad**. Tiene especialidad, manías estilísticas, posturas que ya defendió y no puede contradecir sin explicarse, temas que le apasionan y temas donde es prudente. PLUMA modela exactamente eso: cada periodista sintético es un registro persistente en el sistema con cuatro capas — Identidad, Conducta, Memoria y Repertorio — y un algoritmo de decisión que las usa en cada pieza.

## 5.2 Capa 1 — Identidad

| Campo | Descripción | Ejemplo |
|---|---|---|
| Nombre y avatar | Identidad pública del autor en el blog | "Valentina Ruiz" |
| Biografía | 3–5 líneas visibles en su página de autor | "Economista de formación, alérgica a los eufemismos corporativos..." |
| Especialidades | Verticales que puede firmar (con nivel de dominio 1–5 por vertical) | Economía 5, Tecnología 3 |
| Rol en la redacción | Analista / Columnista / Cronista / Satírico | Columnista |
| Página de autor | Archivo de sus piezas + bio + posturas destacadas | /autor/valentina-ruiz |

**Decisión de diseño ética y estratégica:** la página de autor y/o el pie de cada artículo deben declarar con elegancia que la redacción es asistida por IA bajo dirección editorial humana (texto configurable). Esto no es un capricho: es cumplimiento de las políticas de transparencia emergentes, blindaje reputacional, y —contraintuitivamente— un diferenciador de marca si se hace con personalidad ("Valentina es una periodista sintética entrenada y editada por la redacción de [tu medio]").

## 5.3 Capa 2 — Parámetros de Conducta

Estos son los diales que pediste: la conducta del periodista se define con un panel de parámetros numéricos (0–100) y reglas cualitativas. El algoritmo de redacción los consume en cada pieza.

### Diales de temperamento (0–100)

| Parámetro | Qué controla | Efecto bajo | Efecto alto |
|---|---|---|---|
| **Agudeza crítica** | Cuánto cuestiona los hechos y a los actores | Relata con neutralidad | Interroga motivos, señala contradicciones, nombra ganadores y perdedores |
| **Humor** | Frecuencia e intensidad de la comicidad | Tono sobrio | Ironía recurrente, remates cómicos |
| **Sátira** | Permiso para la exageración con intención crítica | Nunca satiriza | Puede construir piezas enteramente satíricas |
| **Formalidad** | Registro del lenguaje | Cercano, coloquial | Registro de columna dominical |
| **Vehemencia** | Fuerza con que defiende su tesis | Matiza, concede | Afirma, desafía al lector |
| **Empatía** | Sensibilidad ante víctimas y afectados | Distante | Centra la pieza en el impacto humano |
| **Densidad de datos** | Proporción de cifras y evidencia | Narrativo | Cada afirmación lleva su número |
| **Longitud preferida** | Extensión natural de sus piezas | Piezas de 600 palabras | Ensayos de 1.800 |

### Reglas de conducta (cualitativas)

- **Línea editorial:** un texto breve que define su cosmovisión ("escéptica del poder, optimista de la tecnología, defensora del consumidor"). Es el filtro por el que pasan todas sus tesis.
- **Líneas rojas personales:** temas donde este periodista jamás bromea, o que jamás firma (heredan y amplían las líneas rojas globales del sitio del Capítulo 8).
- **Muletillas y firmas estilísticas:** 3–6 rasgos verbales recurrentes que hacen la voz reconocible (una forma de abrir, una estructura de remate, una palabra fetiche usada con moderación). El algoritmo los inyecta con una frecuencia controlada para que no se vuelvan paródicos.
- **Vocabulario prohibido:** clichés vetados ("en el ojo del huracán", "dar la vuelta a la tortilla"), tecnicismos sin explicar, y la lista de muletillas típicas de texto generado por IA — esta última lista es crítica y se mantiene actualizada.
- **Relación con el lector:** cómo se dirige a la audiencia (de tú, de usted, "ustedes ya saben cómo termina esto...") y su estilo de pregunta final.

### Matriz de tono por tipo de noticia

Cada periodista lleva una matriz que cruza el **tipo de noticia** con el **tono dominante y de apoyo** permitidos. Ejemplo para "Valentina" (agudeza 80, humor 55, sátira 40):

| Tipo de noticia | Tono dominante | Tono de apoyo | Sátira permitida |
|---|---|---|---|
| Anuncio corporativo | Analítico | Crítico | Sí (remate) |
| Escándalo político | Crítico | Analítico | Con moderación |
| Tragedia / catástrofe | Informativo-empático | Analítico | **Bloqueada por sistema** |
| Cultura / viral | Humorístico | Opinión | Sí (pieza completa) |
| Dato económico | Analítico | Persuasivo | No |

El bloqueo de sátira en tragedias no es un valor de la matriz: es una regla de sistema inviolable que se impone sobre cualquier configuración (Capítulo 8).

## 5.4 Capa 3 — Memoria editorial

La memoria es lo que separa una persona de una plantilla. Cada periodista mantiene:

- **Registro de posturas:** cada tesis defendida queda archivada como tripleta (tema → postura → pieza). Antes de redactar, el algoritmo consulta este registro: si la nueva tesis contradice una anterior, el periodista debe **reconocerlo en el texto** ("hace tres meses defendí lo contrario; estos datos me obligan a rectificar") — que es exactamente lo que hace creíble a un columnista humano.
- **Historial de cobertura:** qué historias ha seguido, para poder escribir "como anticipamos en marzo..." con enlace interno (SEO y credibilidad en una sola jugada).
- **Memoria de audiencia:** qué piezas suyas generaron más comentarios y qué preguntas finales funcionaron, para que su estilo de conversación evolucione con datos.

## 5.5 El Algoritmo de Decisión Editorial (Fase 3)

Cuando una Pieza investigada llega a la Sala de Redacción, el orquestador ejecuta esta secuencia:

**Paso 1 — Clasificación de la noticia.** El expediente se clasifica en cinco ejes: tema (vertical), gravedad (0–100: de viral ligero a tragedia), polaridad (quiénes son los actores y qué está en disputa), novedad (primicia relativa vs. historia en evolución) y potencial conversacional (¿divide opiniones?).

**Paso 2 — Asignación de periodista.** Se puntúa a cada periodista del banco contra la pieza: dominio del vertical (peso alto), afinidad de su línea editorial con el ángulo detectado, historial con esa historia (quien la empezó, la sigue), y balance de carga (nadie firma 10 piezas seguidas el mismo día: la diversidad de firmas es señal de redacción real). Gana el de mayor puntuación; el editor puede fijar asignaciones manuales por vertical.

**Paso 3 — Selección de ángulo.** El algoritmo genera 3–5 candidatos de tesis a partir del expediente (el hueco detectado por el Investigador es siempre candidato) y los puntúa por: originalidad frente a la cobertura existente, compatibilidad con la línea editorial del periodista, sustento en hechos verificados (una tesis sin datos que la respalden se descarta), y potencial de conversación. La tesis ganadora, el tono dominante y el de apoyo (según la matriz) quedan escritos en la **Ficha de Decisión Editorial** de la pieza — trazabilidad pura.

**Paso 4 — Arquitectura de la pieza.** Se genera un esqueleto argumental propio (nunca calcado de una fuente): gancho → hechos esenciales con atribución (25–35% del texto) → desarrollo de la tesis en 2–4 movimientos argumentales con datos del expediente → contraargumento reconocido y respondido (marca de madurez editorial) → remate en el tono de apoyo → bloque del editor.

## 5.6 Redacción y Autocrítica (Fase 4)

**Redacción en dos pasadas.** Primera pasada: la pieza completa según la ficha y el esqueleto, con la voz del periodista (sus diales, muletillas y prohibiciones inyectados como directrices de estilo). Segunda pasada — **el Corrector Interno** — es un agente distinto con un solo trabajo: atacar el borrador con una lista de verificación:

1. ¿Cada hecho del texto existe en el expediente con el estado correcto (verificado/atribuido)? Todo hecho sin respaldo se elimina o se atribuye. *Regla de oro contra la alucinación: el redactor no puede saber nada que el expediente no sepa.*
2. ¿La proporción interpretación/relato cumple el mínimo del 60%?
3. ¿Hay frases con similitud sospechosa con los extractos de fuentes? (verificación de solapamiento n-grama contra el expediente). Si la hay, se reescribe la sección, no la frase.
4. ¿Suena a la voz del periodista? (verificación de presencia de rasgos estilísticos y ausencia de vocabulario prohibido).
5. ¿El titular promete lo que la pieza cumple? (anti-clickbait: el clickbait dispara el rebote y el rebote mata el SEO).
6. ¿La pieza respeta la matriz de tono y las líneas rojas?

Si el Corrector encuentra fallos, devuelve el borrador con anotaciones y se redacta una revisión (máximo 2 ciclos; si al tercero no pasa, la pieza se marca RETENIDA para revisión humana — el sistema nunca publica "lo menos malo").

## 5.7 Sello del editor y motor de conversación (Fase 7)

Cada pieza cierra con el **Bloque del Editor**, generado con reglas propias:

- **El comentario:** 2–4 líneas en primera persona del periodista firmante, con una postura más desnuda que el cuerpo del artículo (el cuerpo argumenta; el comentario confiesa). Es el espacio de mayor temperatura de la pieza.
- **La pregunta:** nunca el genérico "¿qué opinas?". El algoritmo construye la pregunta desde la polaridad detectada en el Paso 1: plantea el dilema concreto de la noticia en segunda persona ("¿Aceptarías X a cambio de Y?", "¿A quién le crees aquí, y por qué?"). Las preguntas dicotómicas con matiz son las que más comentarios generan.
- **El compromiso de respuesta:** el sistema puede (modo configurable) redactar borradores de respuesta del periodista a los primeros comentarios sustantivos, que el editor humano aprueba con un clic. Un autor que responde multiplica los comentarios; los comentarios son contenido fresco e indexable y señal de comunidad viva.

## 5.8 El banco de periodistas: composición recomendada

Para un medio que aspira a 100k–400k visitas, el banco inicial ideal es de **3 a 5 periodistas** con perfiles complementarios y verticales asignados — suficientes para dar sensación de redacción, pocos para que cada voz acumule historial y audiencia propia. Ejemplo de plantilla inicial: un analista de datos sobrio (el ancla de credibilidad), una columnista crítica vehemente (la que genera conversación), y un cronista satírico para cultura y virales (el que genera compartidos). El panel permite crear, clonar, jubilar y hacer evolucionar periodistas (sus diales pueden ajustarse con el tiempo, y su memoria hace que el cambio se sienta como crecimiento, no como reemplazo).

---
# CAPÍTULO 6 — MOTOR SEO: LA CAPA DE OPTIMIZACIÓN

## 6.1 Misión

Tomar una pieza editorial ya excelente y vestirla para competir en el SERP sin deformarla. El Motor SEO nunca reescribe argumentos: ajusta superficies (titular, metadatos, estructura de encabezados, enlazado, datos estructurados).

## 6.2 Trabajo sobre la pieza

- **Keyword principal y secundarias:** derivadas de la tendencia origen (el Radar ya sabe qué busca la gente) y de las preguntas relacionadas del SERP. La principal debe aparecer en el titular (idealmente en los primeros 50 caracteres), la URL, el primer párrafo y al menos un H2 — y ni una vez más de forma forzada.
- **Doble titular:** el sistema genera un titular editorial (el que se muestra en el sitio, con la voz del periodista) y un titular SEO (la etiqueta title, ≤60 caracteres, orientado a la búsqueda). Son cosas distintas y tratarlas igual sacrifica una de las dos.
- **Meta descripción:** ≤155 caracteres, con la promesa editorial de la pieza (el ángulo, no el resumen — el resumen ya lo da todo el mundo).
- **Estructura de encabezados como respuestas:** los H2/H3 se reformulan, cuando es natural, como las preguntas que la gente hace sobre el tema (candidatos directos a "People Also Ask" y fragmentos destacados), sin sacrificar la arquitectura argumental.
- **Enlazado interno automático con juicio:** 2–5 enlaces a piezas propias relacionadas (la memoria de cobertura del periodista es la fuente perfecta), priorizando las piezas "marea" de larga vida. Enlazado externo: todas las fuentes del expediente citadas en el texto llevan su enlace.
- **Imagen destacada:** generada o seleccionada de bancos con licencia, con nombre de archivo descriptivo, alt text con la keyword natural y compresión automática (WebP). Jamás imágenes de otros medios.
- **Datos estructurados:** NewsArticle (o AnalysisNewsArticle/OpinionNewsArticle según el tipo de pieza — la distinción existe en schema.org y casi nadie la usa: ventaja), con autor enlazado a su página de perfil (señal E-E-A-T), fechas de publicación y modificación, y editor con logo.

## 6.3 Trabajo sobre el sitio

- **Sitemap de noticias** dedicado (protocolo Google News: solo piezas de las últimas 48h) además del sitemap general, con ping de indexación al publicar (API de indexación cuando aplique).
- **Auditoría de canibalización:** antes de publicar, el motor verifica si el sitio ya tiene una pieza posicionando por la misma keyword; si la hay, propone convertir la nueva en actualización de la existente o diferenciar el enfoque de keywords. Dos piezas propias compitiendo entre sí es tráfico regalado.
- **Higiene de rendimiento:** el plugin en sí no debe sabotear el Core Web Vitals del sitio — sus assets de administración jamás cargan en el frontend, y el frontend solo recibe el bloque del editor y el schema (peso casi nulo).
- **Compatibilidad, no competencia:** si el sitio ya usa Rank Math o Yoast, PLUMA escribe en sus campos en lugar de duplicar la capa SEO. Detectar e integrarse con el ecosistema existente es diseño premium; ignorarlo es diseño de plugin barato.

## 6.4 Bucle de retroalimentación

El Motor SEO se conecta a Search Console y alimenta al Radar y a la Sala de Redacción con datos reales: qué piezas ganan impresiones sin clics (titular débil → regenerar titular SEO), qué keywords emergen en posiciones 5–15 (candidatas a pieza de refuerzo), qué periodista posiciona mejor en qué vertical (ajuste de asignaciones). Este bucle es lo que convierte el plugin de una máquina de publicar en una máquina que aprende.

---

# CAPÍTULO 7 — EL TAXÓNOMO: CATEGORÍAS Y ETIQUETAS INTELIGENTES

## 7.1 Reglas de arquitectura taxonómica

- **Categorías: pocas, estables, estratégicas.** 5–8 en total, definidas por el editor, correspondientes a los verticales del Radar. El Taxónomo **jamás crea categorías**: solo asigna (una por pieza, la más específica aplicable). Las categorías son la arquitectura del sitio y la arquitectura no se improvisa pieza a pieza.
- **Etiquetas: los actores y temas concretos.** Personas, organizaciones, productos, eventos y conceptos específicos de la pieza. Regla: 3–6 por pieza.

## 7.2 Algoritmo de etiquetado

1. **Extracción de entidades** del expediente (no solo del texto final): actores, lugares, eventos, conceptos.
2. **Reconciliación contra el vocabulario existente:** cada entidad se compara con las etiquetas del sitio por coincidencia exacta, alias conocidos ("IA" = "inteligencia artificial") y similitud. Si existe, se reutiliza — siempre. La fragmentación de etiquetas ("elecciones2026", "elecciones-2026", "eleccion 2026") crea páginas de archivo duplicadas y débiles que diluyen el SEO del sitio entero.
3. **Creación con umbral:** una etiqueta nueva solo nace si la entidad es central en la pieza (no mención de paso) y no existe equivalente. Opcionalmente, las etiquetas nuevas nacen "en cuarentena" (no indexables) hasta acumular 3+ piezas: solo los archivos con contenido real merecen estar en Google.
4. **Mantenimiento programado:** un trabajo semanal detecta etiquetas huérfanas (1 pieza, sin crecimiento en 90 días) y propone fusiones o eliminaciones al editor. La taxonomía, como el jardín, se poda.

## 7.3 Las páginas de archivo como activos

Las páginas de categoría y de etiquetas frecuentes se enriquecen automáticamente: una descripción editorial (redactada por el periodista del vertical, con su voz), la pieza "marea" destacada del tema, y datos estructurados de colección. Las páginas de archivo bien tratadas posicionan por keywords amplias que ningún artículo individual puede ganar.

---

# CAPÍTULO 8 — COMPUERTAS: CALIDAD, RIESGO Y ORIGINALIDAD

*Ninguna pieza llega al Publicador sin atravesar las tres compuertas. Este capítulo es el sistema inmunológico del producto.*

## 8.1 Compuerta de Calidad

Puntuación compuesta (0–100) sobre: cumplimiento de proporción interpretativa, densidad de sustento (afirmaciones con respaldo del expediente), legibilidad (longitud de frases y párrafos según el registro del periodista), presencia de la voz (rasgos estilísticos detectados), y estructura completa (gancho, tesis, contraargumento, bloque del editor). Umbral configurable; por debajo, RETENIDA con diagnóstico.

## 8.2 Compuerta de Riesgo

Clasificadores en cascada que degradan el modo de publicación (Autónomo → Copiloto → Piloto) o retienen:

- **Sensibilidad temática:** tragedias, menores, salud, violencia → degradación automática de modo y bloqueo absoluto de sátira/humor (regla de sistema, por encima de toda configuración de periodista).
- **Riesgo de difamación:** la pieza afirma hechos negativos sobre personas identificables → verificación de que cada afirmación esté en estado *verificado* multifuente, redactada como hecho atribuido o como opinión claramente marcada; si no, RETENIDA para humano. Las opiniones son libres; las afirmaciones falsas sobre personas son demandas.
- **Hechos disputados sin señalar:** si el expediente marca disputas y la pieza las presenta como consenso, se devuelve a redacción.
- **Detección de temas legalmente regulados** (según jurisdicción configurada): consejos de salud, financieros o legales reciben los descargos correspondientes o degradación.

## 8.3 Compuerta de Originalidad

- **Solapamiento contra fuentes:** verificación final de n-gramas contra todos los extractos del expediente (la del Corrector Interno, repetida sobre la versión final — cinturón y tirantes).
- **Solapamiento contra el propio sitio:** ¿es demasiado parecida a una pieza propia anterior? (auto-plagio y canibalización).
- **Huella de ganancia de información:** heurística que estima si la pieza dice algo que las fuentes no dicen (presencia de tesis, contexto histórico añadido, cruces de datos). Es la compuerta anti-"scaled content abuse": si la pieza no añade, no sale.

## 8.4 El expediente de auditoría

Toda decisión de compuerta (puntuaciones, degradaciones, retenciones y sus motivos) se escribe en el expediente de la Pieza. El panel muestra por qué cada pieza salió, se retuvo o se degradó. Sin esto, el sistema es una caja negra imposible de calibrar y de defender.

---

# CAPÍTULO 9 — EL PUBLICADOR: PROGRAMACIÓN, CUOTAS Y EJECUCIÓN

## 9.1 El contrato del orquestador

Tu requisito, convertido en contrato de sistema: *"El editor configura N publicaciones al día. Cada vez que el motor se ejecuta, avanza todas las tareas pendientes del pipeline y publica lo necesario para cumplir la cuota, sin excederla, sin duplicar y sin publicar basura para rellenar."*

## 9.2 Configuración de cadencia

- **Cuota diaria:** número objetivo de publicaciones (ej. 6/día), con mínimo y máximo (ej. mín. 3, máx. 8 si hay tendencias excepcionales — el editor decide si la cuota es rígida o elástica).
- **Ventanas de publicación:** franjas horarias permitidas con pesos (ej. 07:00–09:00 peso alto, 12:00–14:00 medio, 19:00–21:00 alto), alineadas con los picos de la audiencia según analítica. El sistema reparte la cuota entre ventanas con **separación mínima entre piezas** (ej. 45 min) y con "jitter" aleatorio de minutos: nada delata más a un sitio automatizado que publicar cada día exactamente a en punto.
- **Cuotas por vertical y por periodista:** techos opcionales (máx. 2 de política/día; máx. 3 firmas de Valentina/día) para mantener el equilibrio de portada y la verosimilitud de la redacción.
- **Modo pausa y modo respeto:** pausa global de un clic (vacaciones, crisis), y "modo respeto" que congela humor y sátira en todo el sitio ante una tragedia mayor (activable manualmente o por señal del clasificador de gravedad del Radar).

## 9.3 Anatomía de una ejecución

Cada disparo del motor (idealmente cada 10–15 minutos) ejecuta, en orden y con presupuesto de tiempo limitado:

1. **Adquirir el candado global** (si otra ejecución vive, salir en silencio — jamás dos motores en paralelo).
2. **Avanzar el pipeline:** tomar los lotes pendientes de cada estado (investigar lo detectado, redactar lo investigado, optimizar lo redactado...) hasta agotar el presupuesto de la ejecución. Prioridad por Puntuación de Oportunidad y por perecibilidad (las tendencias relámpago primero).
3. **Consultar la cuota:** ¿cuántas piezas se han publicado hoy? ¿cuántas están programadas? ¿cuál es la próxima ranura de ventana disponible?
4. **Programar:** asignar las piezas APROBADAS de mayor puntuación a las próximas ranuras hasta cubrir la proyección del día. Si hay más piezas aprobadas que cuota, las excedentes esperan (las perecederas pueden expirar y descartarse solas: mejor no publicar que publicar tarde).
5. **Publicar lo vencido:** toda pieza programada cuya hora llegó se convierte en post de WordPress (con su capa SEO, taxonomía, schema y bloque del editor), se dispara el ping de sitemap y los eventos `pluma/pieza_publicada`.
6. **Escasez honesta:** si no hay piezas aprobadas suficientes para la cuota, el sistema **no rebaja los umbrales para rellenar**. Registra el déficit, lo notifica, y el editor decide si el problema es de Radar (pocas tendencias afines), de compuertas (demasiado duras) o del día (a veces no pasa nada, y publicar 3 piezas buenas gana a publicar 6 mediocres — siempre).
7. **Liberar el candado y escribir bitácora** de la ejecución (qué avanzó, qué falló, cuánto costó en tokens/APIs).

## 9.4 Infraestructura de ejecución

- **WP-Cron no es un cron:** solo corre cuando alguien visita el sitio — inaceptable para un sistema editorial. El diseño exige **cron real del servidor** (o servicio externo de ping) golpeando el punto de entrada del motor, con WP-Cron deshabilitado para estas tareas. El instalador del plugin detecta la configuración y guía al usuario con instrucciones exactas para su hosting.
- **Reintentos con retroceso exponencial** para fallos de APIs externas, con estado FALLIDA visible y alertas si una pieza fracasa 3 veces.
- **Presupuestos de coste:** límite diario configurable de gasto en APIs de lenguaje y datos; al 80% se notifica, al 100% el motor pausa la generación (nunca la publicación de lo ya aprobado).

---
# CAPÍTULO 10 — DISEÑO PREMIUM DE LA EXPERIENCIA DE ADMINISTRACIÓN

*Un plugin de este calibre no puede sentirse como un formulario de ajustes. Debe sentirse como entrar a la sala de redacción de un medio de primera línea. Este capítulo define esa experiencia.*

## 10.1 Principios de diseño de la interfaz

- **Metáfora de redacción, no de configuración:** las secciones se llaman Sala de Tendencias, Mesa Editorial, Banco de Periodistas, Sala de Revisión, Hemeroteca — no "Settings > General > Advanced".
- **Un estado de sistema siempre visible:** una barra superior persistente con el pulso del día: modo actual (Piloto/Copiloto/Autónomo), cuota (4/6 publicadas), próxima publicación (12:40), salud del motor (última ejecución, coste del día). El editor debe saber en tres segundos si todo está bien.
- **Densidad calibrada:** los tableros muestran mucho, pero jerarquizado — tipografía editorial (una serif de carácter para titulares del panel, una sans limpia para datos), espaciado generoso, color con significado (verde publicada, ámbar en revisión, rojo retenida) y cero ruido decorativo.
- **Modo oscuro nativo** y diseño responsivo real: la ventana de veto del modo Copiloto se usará desde el teléfono, y esa pantalla móvil debe ser perfecta.
- **Cada número es una puerta:** todo dato del panel (una puntuación, un estado, un coste) es clicable y lleva al detalle. Nada de métricas decorativas.

## 10.2 Las pantallas

### La Portada (dashboard)
El día de un vistazo: cuota y programación en línea de tiempo, tendencias calientes ahora, piezas en cada estado del pipeline (como un tablero kanban de redacción), alertas (retenidas esperando decisión, déficits de cuota, fallos), y los resultados de ayer (tráfico, piezas top, comentarios).

### Sala de Tendencias
El radar en vivo: tarjetas de tendencias con su Puntuación de Oportunidad desglosada (velocidad, afinidad, hueco, vida útil), gráfico de aceleración, quién la está cubriendo ya, y acciones directas: *Cubrir ahora* (salta la cola), *Ignorar*, *Vigilar*. Aquí el editor siente el pulso del día y puede intervenir la agenda con un clic.

### Mesa Editorial (el pipeline)
El kanban de Piezas por estado, con la Ficha de Decisión Editorial visible en cada tarjeta (periodista, ángulo, tesis, tonos). Abrir una pieza muestra el expediente completo: fuentes con sus extractos y estados de verificación, borradores con las anotaciones del Corrector Interno, puntuaciones de compuertas con desglose, y el diff entre versiones. Desde aquí el editor puede editar, reasignar periodista, forzar aprobación o descartar — siempre con registro de quién hizo qué.

### Banco de Periodistas
La pantalla estrella. Cada periodista es una tarjeta con avatar, especialidades y sus métricas vivas (piezas, tráfico medio, comentarios medios, verticales donde más posiciona). Su ficha abre el **estudio de conducta**: los diales de temperamento como controles deslizantes con vista previa en vivo (un párrafo de muestra que se re-redacta al mover un dial — la función que enamora en las demos), la matriz de tonos editable, las líneas rojas, las muletillas, y su memoria navegable (posturas defendidas, historias seguidas). Acciones: crear desde plantilla, clonar, ajustar, jubilar (sus piezas quedan, deja de recibir asignaciones).

### Sala de Revisión
La bandeja de lo que espera decisión humana: piezas RETENIDAS con su diagnóstico exacto ("afirmación sobre persona identificable sin doble fuente, párrafo 4"), y en modo Copiloto, la cola de veto con cuenta regresiva. Diseñada para decidir rápido: lectura limpia, diagnóstico arriba, tres botones (aprobar / devolver con nota / descartar). Con notificaciones por correo/Telegram/Slack con enlaces de acción directa.

### Estudio SEO y Taxonomía
Estado de indexación por pieza, keywords ganadas y en el umbral (posiciones 5–15, con botón "crear pieza de refuerzo"), salud taxonómica (etiquetas en cuarentena, propuestas de fusión), y auditoría de canibalización.

### Sala de Máquinas
La bitácora del motor: ejecuciones, duración, piezas avanzadas, errores con reintentos, coste por pieza y por día contra presupuesto, estado de cada API conectada, y las llaves/configuración técnica. Transparencia total del coste operativo: el editor debe saber cuánto le cuesta cada artículo publicado.

## 10.3 La experiencia de instalación (onboarding)

El primer arranque es un asistente de cinco actos que configura un sistema funcional en menos de 20 minutos: (1) verificación técnica del hosting y configuración del cron real con instrucciones específicas del proveedor detectado; (2) conexión de llaves de APIs con prueba en vivo; (3) definición de línea editorial y verticales (con importación de las categorías existentes del sitio); (4) creación del primer periodista desde plantillas con el estudio de conducta; (5) elección de modo (con Piloto pre-seleccionado y una explicación honesta de por qué empezar ahí). Al terminar, el sistema ejecuta su primer ciclo en vivo delante del usuario y produce su primer borrador: el momento "wow" tiene que ocurrir en la primera sesión.

---

# CAPÍTULO 11 — MODELO DE DATOS

*Descripción conceptual de las tablas propias del plugin (prefijo `pluma_`). El detalle de columnas se cierra en la fase de implementación; aquí se fija la estructura y las relaciones.*

| Tabla | Contenido | Relaciones clave |
|---|---|---|
| `tendencias` | Tendencias detectadas: término, señales por sensor, puntuación desglosada, clasificación de vida útil, huella semántica, estado | 1→N con piezas |
| `piezas` | El objeto central: estado, ficha de decisión editorial, puntuaciones de compuertas, modo de publicación efectivo, post de WP resultante | N→1 tendencia, N→1 periodista, 1→1 post WP |
| `fuentes` | Expediente de investigación: URL, medio, nivel de confianza, extractos con sello temporal, estado de verificación de cada hecho | N→1 pieza |
| `periodistas` | Identidad, diales de conducta, matriz de tonos, reglas cualitativas, estado (activo/jubilado) | 1→N piezas |
| `memoria_editorial` | Posturas (tema→postura→pieza), historias seguidas, aprendizajes de audiencia por periodista | N→1 periodista |
| `borradores` | Versiones de cada pieza con las anotaciones del Corrector Interno (el historial de revisión) | N→1 pieza |
| `cola_publicacion` | Ranuras programadas: pieza, ventana, hora exacta con jitter, estado | 1→1 pieza |
| `bitacora_motor` | Cada ejecución: candado, lotes procesados, errores, costes | — |
| `auditoria` | Toda decisión de sistema o de humano sobre una pieza, con actor, momento y motivo | N→1 pieza |
| `vocabulario` | Alias de entidades para la reconciliación de etiquetas, etiquetas en cuarentena, listas de confianza y negra de fuentes | — |

Decisiones transversales: claves foráneas lógicas con índices sobre los campos de estado+fecha (las consultas del motor son siempre "dame N piezas en estado X por prioridad"); los expedientes voluminosos (extractos, borradores) se comprimen; política de retención configurable (expedientes completos 90 días, resumen de auditoría permanente); y exportación completa del banco de periodistas y su memoria (el activo más valioso del usuario debe ser portable).

---

# CAPÍTULO 12 — SEGURIDAD, RENDIMIENTO Y ESCALABILIDAD

## 12.1 Seguridad

- **Las llaves de API cifradas en reposo** (nunca en texto plano en la tabla de opciones), con permisos de visualización solo-escritura en el panel.
- **Capacidades de WordPress propias** (`pluma_gestionar_periodistas`, `pluma_aprobar_piezas`, `pluma_configurar_motor`): un rol de "editor de revisión" puede aprobar piezas sin poder tocar la configuración del motor.
- **El punto de entrada del cron autenticado por token secreto** rotable, con limitación de tasa.
- **Sanitización paranoica del contenido externo:** todo lo que llega de fuentes (HTML de artículos, respuestas de APIs) se trata como hostil — se limpia antes de almacenar y jamás se ejecuta. Las instrucciones que pudieran venir embebidas en contenido de fuentes (inyección de prompts) se neutralizan tratando todo material de expediente como datos, nunca como órdenes al redactor.
- **Registro de auditoría inmutable** de acciones humanas y automáticas (Capítulo 11).

## 12.2 Rendimiento

- Cero peso en el frontend salvo el bloque del editor y el schema; assets de administración con carga diferida por pantalla.
- El motor con presupuesto de tiempo por ejecución y lotes pequeños: mejor 6 ejecuciones cortas por hora que una larga que muere por timeout.
- Caché de señales del Radar (las APIs de tendencias se consultan una vez por ciclo, no por pantalla).

## 12.3 Escalabilidad

- El diseño de estados+colas permite pasar de 3 a 30 piezas/día sin cambiar arquitectura: solo más ejecuciones y más presupuesto.
- Multisitio de WordPress soportado con bancos de periodistas y cuotas por sitio.
- El Proveedor de Lenguaje abstracto permite enrutar tareas a modelos distintos por coste (clasificar con un modelo económico, redactar con el mejor).

---

# CAPÍTULO 13 — GOBERNANZA EDITORIAL Y CUMPLIMIENTO

*Las reglas que están por encima de toda configuración. Grabadas en el sistema, no en las opciones.*

1. **Transparencia de autoría:** el sitio declara la asistencia de IA en la redacción (formato configurable, existencia no negociable en el diseño). Es la dirección de las políticas de plataformas y reguladores, y el escudo reputacional del medio.
2. **Sátira y humor bloqueados por sistema** en tragedias, víctimas y menores.
3. **Ninguna afirmación fáctica negativa sobre personas identificables** sale en modo autónomo sin doble fuente verificada; en la duda, retención humana.
4. **Citar y enlazar siempre;** reproducir, jamás. Los extractos del expediente son material de trabajo interno, no de publicación.
5. **Nunca rellenar la cuota rebajando umbrales.** El déficit se reporta, no se disimula.
6. **Derecho de rectificación de primera clase:** un flujo dedicado para correcciones (banner de corrección en la pieza, registro de qué cambió y cuándo, actualización del schema con la fecha de modificación). Los medios serios se distinguen por cómo corrigen.
7. **Cumplimiento con Google:** el sistema entero está diseñado para pasar la prueba del "helpful content": ganancia de información obligatoria, autores con página y trayectoria, fuentes citadas, sin clickbait, sin volumen por volumen. La compuerta de originalidad (8.3) es la implementación técnica de este compromiso.

---

# CAPÍTULO 14 — PLAN DE CONSTRUCCIÓN POR ETAPAS

*Cómo se come este elefante: cinco etapas, cada una entrega algo usable.*

**Etapa 1 — El esqueleto que camina (semanas 1–4).** Núcleo de servicios, tabla de Piezas con su máquina de estados, motor cron con candado y bitácora, un sensor de Radar (Google Trends), y publicación de prueba en modo Piloto. Criterio de salida: el sistema detecta una tendencia y crea un borrador trazable de punta a punta, aunque sea rudimentario.

**Etapa 2 — El periodista (semanas 5–9).** Banco de periodistas con diales de conducta, algoritmo de decisión editorial, redacción en dos pasadas con Corrector Interno, memoria de posturas, bloque del editor. Criterio de salida: dos periodistas con voces distinguibles a ciegas por un lector humano.

**Etapa 3 — La capa competitiva (semanas 10–13).** Motor SEO completo, Taxónomo, compuertas de calidad/riesgo/originalidad, modos Copiloto y Autónomo con degradación por sensibilidad, sala de revisión con notificaciones. Criterio de salida: una semana entera en Copiloto sin que ninguna pieza publicada requiera corrección posterior.

**Etapa 4 — La experiencia premium (semanas 14–17).** El panel completo del Capítulo 10, el onboarding de cinco actos, el estudio de conducta con vista previa en vivo, presupuestos de coste. Criterio de salida: un usuario nuevo instala, configura y obtiene su primer borrador en menos de 20 minutos sin documentación.

**Etapa 5 — La máquina que aprende (semanas 18–22).** Bucle de Search Console, memoria de audiencia, piezas de refuerzo y actualización ("dos golpes"), respuestas a comentarios asistidas, informes editoriales semanales. Criterio de salida: el sistema propone decisiones (refuerzos, ajustes de asignación) basadas en datos reales del sitio.

**Después:** distribución (newsletter y redes automáticas por pieza), multisitio, mercado de plantillas de periodistas, y —si el destino del producto es comercial— licenciamiento por niveles (por cuota diaria y número de periodistas).

---

# EPÍLOGO: LAS TRES VERDADES DEL PROYECTO

**Primera:** este plugin no compite con otros plugins; compite con la tentación de su propio dueño de subir la cuota y bajar los umbrales. La arquitectura pone frenos porque el negocio de 100k–400k visitas mensuales se construye sobre la confianza de Google y de los lectores, y esa confianza se pierde una sola vez.

**Segunda:** el activo que este sistema acumula no son los artículos: son los periodistas. Sus voces, sus memorias, sus posturas y su relación con la audiencia son lo que ningún competidor puede copiar mañana. Cuida el Banco de Periodistas como el corazón del producto, porque lo es.

**Tercera:** la autonomía es una escalera, no un interruptor. Piloto → Copiloto → Autónomo es también el camino de tu propia confianza en el sistema. Los mejores medios sintéticos del futuro serán los que subieron esa escalera despacio y con las compuertas encendidas.

*— Fin del libro de arquitectura —*
