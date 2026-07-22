# GOVERNANCE.md — PLUMA Engine
# Políticas Permanentes

---

## SECCIÓN 1: CALIDAD DE CÓDIGO

### 1.1 Tipado estricto
Todo archivo de `src/` abre con `declare(strict_types=1);`. Sin excepciones.

### 1.2 Inmutabilidad
| Construcción | Regla |
|---|---|
| DTOs | `final readonly class` — cero setters |
| Eventos | propiedades `readonly` en constructor |
| Fichas (Decisión Editorial, Expediente) | inmutables; toda revisión crea versión nueva en `pluma_borradores` |
| Configuración de periodista | los cambios crean versión con fecha; la memoria referencia la versión vigente al redactar |

### 1.3 Límites de complejidad
| Métrica | Máximo |
|---|---|
| Líneas de lógica por método | 20 |
| Líneas por clase | 200 |
| Complejidad ciclomática por método | 10 |
| Parámetros por método | 5 |
| Anidamiento | 3 |

Al exceder: extraer, no ampliar el límite. Excepción única documentable: matrices de decisión declarativas (matriz de tonos) pueden superar líneas de clase si son datos, no lógica.

### 1.4 Nomenclatura
| Construcción | Convención | Ejemplo |
|---|---|---|
| Clases | PascalCase en español del dominio | `CorrectorInterno`, `ColaPublicacion` |
| Interfaces | sufijo `Interface` | `SensorInterface`, `LenguajeInterface` |
| Enums | PascalCase + casos PascalCase | `EstadoPieza::EnRedaccion` |
| Métodos | camelCase verbo primero | `asignarPeriodista()`, `evaluarRiesgo()` |
| Hooks/eventos | `pluma/` + snake_case | `pluma/pieza_publicada` |
| Opciones WP | `pluma_` + snake_case | `pluma_modo_global` |
| Tablas | `{$wpdb->prefix}pluma_` + snake_case | `wp_pluma_piezas` |
| Claves de caché/transients | `pluma_{dominio}_{contexto}` | `pluma_radar_senales` |
| Capacidades | `pluma_` + verbo_objeto | `pluma_aprobar_piezas` |

### 1.5 Prohibido en cualquier archivo no-test
`dd/dump/var_dump/print_r/echo` (fuera de vistas escapadas) · `exit/die` (salvo guarda `ABSPATH`) · `eval` · `extract` · `$_GET/$_POST/$_REQUEST` crudos (siempre vía Request sanitizado) · SQL interpolado (siempre `$wpdb->prepare`) · `@` supresor de errores · credenciales/IPs/URLs de API hardcodeadas · código muerto comentado · desactivación de compuertas por flag.

---

## SECCIÓN 2: POLÍTICA EDITORIAL COMO CÓDIGO
*Las reglas del Capítulo 13 del Libro no son texto: son invariantes verificados por tests de arquitectura.*

2.1 No existe ruta de código que publique una Pieza sin registro de las tres compuertas en `pluma_auditoria`. Test de arquitectura obligatorio.
2.2 La degradación por sensibilidad (tragedia/menores/salud → nunca Autónomo, sátira bloqueada) se implementa en `Pluma\Compuertas` y NINGUNA opción de usuario la anula. Test obligatorio.
2.3 Afirmación fáctica negativa sobre persona identificable sin doble fuente `verificada` → estado RETENIDA. Test obligatorio.
2.4 El redactor solo conoce el expediente: toda afirmación del borrador debe ser trazable a un hecho del expediente (anti-alucinación). El Corrector Interno lo verifica; el test verifica al Corrector.
2.5 Extractos de fuentes: material interno, longitud acotada, jamás reproducidos en la pieza publicada; toda fuente usada se cita y enlaza.
2.6 Transparencia de autoría IA: el bloque configurable existe siempre; la opción controla el formato, no la existencia.
2.7 Escasez honesta (CLAUDE.md § Orquestador): déficit se reporta, umbrales no se tocan.

---

## SECCIÓN 3: SEGURIDAD

3.1 Toda entrada sanitizada, toda salida escapada, todo endpoint con nonce + capacidad. `permission_callback` real siempre.
3.2 Llaves de API cifradas en reposo (sodium/libsodium vía `pluma_cifrado`), jamás en texto plano en `wp_options`, jamás logueadas, jamás devueltas por REST.
3.3 SSRF: toda URL externa (fuentes, feeds) pasa `validarUrlExterna()` — esquema https, sin IPs privadas, sin redirecciones a rango privado — antes de cualquier `wp_remote_get`.
3.4 Inyección de prompts: contenido de fuentes = datos. El armado de peticiones al proveedor separa instrucciones de material con delimitación estricta y el material jamás se interpreta como órdenes. Test con corpus adversarial obligatorio.
3.5 Uploads/medios: solo los flujos de imagen destacada; MIME verificado en servidor; nombre UUID.
3.6 Endpoint del cron: token secreto rotable + rate limit + candado. Jamás GET sin token.
3.7 `composer audit` y auditoría de dependencias JS en la puerta `/qa`. Dependencias con CVE crítico bloquean release.

---

## SECCIÓN 4: TESTING Y GATES

4.1 Cobertura por tipo: Unit (lógica pura: puntuaciones, matrices, reconciliación, clamps) · Integración WP (repositorios, REST, capacidades, eventos, con `wp-env`) · E2E Playwright (onboarding completo, ciclo Pieza de punta a punta en Piloto, veto en Copiloto, panel).
4.2 Todo bug corregido deja un test de regresión con el número de ticket. Tres apariciones del mismo bug = propiedad del sistema → tarea de arquitectura, no tercer parche.
4.3 Los módulos con azar (jitter, selección) se testean con semilla inyectable. Prohibido `random_*` directo: siempre vía `AzarInterface`.
4.4 Proveedores externos: dobles de prueba con contratos grabados (fixtures de respuestas reales anonimizadas). La suite NUNCA llama APIs reales.
4.5 Puerta `/qa` (todo debe salir 0): `composer lint` (PHPCS) → `composer analyse` (PHPStan L8) → `composer test` → `npm test` → `npm run build` → `composer audit`.

---

## SECCIÓN 5: RELEASE Y VENTA
*El producto se comercializa. Un release defectuoso en el WordPress de un cliente es una devolución y una reseña de una estrella.*

5.1 Versionado SemVer. Toda release: rama `release/x.y.z`, CHANGELOG con cambios visibles para el cliente, migración de esquema probada sobre copia de datos reales de la versión anterior (N-1 → N verificado siempre).
5.2 Paquete de distribución: build reproducible (`composer install --no-dev` + scoping de dependencias + assets compilados + solo archivos de producción). El ZIP se instala en un WP limpio como smoke test obligatorio antes de publicar.
5.3 Matriz de compatibilidad testeada por release: {WP mínimo, WP latest} × {PHP 8.2, 8.3} × {con Yoast, con Rank Math, sin SEO plugin}.
5.4 Licenciamiento y actualizaciones: verificación de licencia con degradación elegante (sin licencia = el plugin no rompe el sitio ni secuestra contenido: pasa a solo-lectura de lo ya publicado). Servidor de actualizaciones propio con firmas.
5.5 Telemetría: opt-in explícito, anónima, documentada. Jamás contenido del cliente ni llaves.
5.6 Soporte de campo: modo diagnóstico exportable (bitácora del motor + versiones + conflictos detectados, sin secretos) para tickets de soporte.
5.7 Documentación de venta mínima por release: guía de instalación, guía del onboarding, referencia de cada pantalla, FAQ de conflictos conocidos.

---

## SECCIÓN 6: DEFINICIÓN DE HECHO (DoD)

Una tarea está DONE cuando: inventario del PLAN GUARDIAN completo y entregado · gates de §4.5 en verde con evidencia · DELIVERY GUARDIAN de CLAUDE.md íntegro · invariantes de §2 intactos (suite de arquitectura en verde) · deuda nueva registrada, no escondida. Cualquier otra definición de "hecho" es complacencia.
