# Informe de revisión de código y seguridad

Proyecto: Registro de vehículos de la cooperativa de taxis (HU-03), parte de la API de la app tipo Uber
Estudiante: Juan José Garzón Cárdenas
Fecha: 2026-11-14
Commit revisado: 94e701d (antes de las correcciones)

## 1. Herramientas y criterios

- **Linter:** PHP_CodeSniffer 3.7.2 con las reglas PSR-12 y `phpcs-security-audit` (reglas de seguridad), y PHPStan 2.3.5 en nivel máximo. Configuración en `phpcs.xml` y `phpstan.neon` (el proyecto es PHP, por eso no hay `pyproject.toml`). Antes: phpcs de seguridad con 2 avisos y PHPStan con 3 errores. Después: 0 y 0.
- **Auditoría de dependencias:** `composer audit` — **no se pudo ejecutar** (no hay `composer.lock`, y el entorno donde se hizo la revisión no llega a Packagist). Queda como pendiente P-01.
- **Lista de revisión:** la de la semana 6 (corrección y pruebas, diseño y legibilidad, seguridad). Pruebas: 8 antes, 12 después, todas pasan, y `git diff 94e701d -- tests/RegistroVehiculosTest.php` sale vacío (ninguna prueba se modificó).
- **Revisión con IA:** Claude (chat) con el prompt de revisión de la semana (ver sección 3).

### Puntos de seguridad revisados

| Punto | ¿Aplica? | Resultado |
|---|---|---|
| Inyección (consultas con parámetros) | No, todavía: no hay base de datos ni consultas; los datos viven en un arreglo en memoria | Sin hallazgos. Se revisa de nuevo al pasar a PostgreSQL (P-03) |
| Secretos (código, historial, .gitignore, .env.example) | Sí | Revisé el historial completo (`git log -p`) y el código buscando password, secret, key y token: sin coincidencias. `.env` ya estaba en `.gitignore`; faltaba `.env.example` (H-05) |
| Contraseñas (scrypt, bcrypt o argon2 con sal) | No: este repositorio no guarda contraseñas | En la API real serán con bcrypt (ADR-001, RNF-01) |
| Control de acceso (rol verificado y prueba de abuso) | No, todavía: la clase no tiene usuarios ni capa HTTP; el rol se controla en la API | Pendiente P-02. Sí hay pruebas de abuso de la regla de la placa (H-01) |
| Dependencias (existen, necesarias, versión fijada, pip-audit) | Sí | `phpunit` existe y es necesaria. Versión sin fijar: no hay `composer.lock` (H-04, P-01) |

## 2. Tabla de hallazgos

(Las líneas son las del commit 94e701d.)

| Id | Archivo y línea | Problema | Categoría (OWASP o tipo) | Severidad | Corrección | Commit |
|---|---|---|---|---|---|---|
| H-01 | src/RegistroVehiculos.php:41 | `trim` y `strtoupper` no limpian espacios Unicode ni caracteres invisibles: «TXY123» + espacio de no separación entra como placa distinta, y una placa de solo espacios Unicode se acepta como no vacía. Rompe «obligatoria y no repetida» (N-10) | A04 Diseño inseguro / validación de entrada | Media | Limpieza con `preg_replace` Unicode (`\p{Z}`, `\p{C}`), `mb_strtoupper` y rechazo de texto no UTF-8; 4 pruebas de abuso nuevas | fbeaf66 (pruebas en rojo), f53e449 |
| H-02 | src/RegistroVehiculos.php:11,13,33 | PHPStan: 3 errores por arreglos sin tipo; funciones sin docstring | Mantenibilidad | Baja | PHPDoc con la forma del arreglo (`Vehiculo`) y docstrings con qué hace, recibe, devuelve y lanza; `strict_types` | c69db10 |
| H-03 | tests/bootstrap.php:3-10 | Autoload propio que arma una ruta de archivo con el nombre de la clase (2 avisos de phpcs-security-audit) y repite lo que ya hace Composer | A01 Control de acceso (path traversal) / duplicación | Baja | Usa `vendor/autoload.php` de Composer | b86371e |
| H-04 | composer.json:5-9 | Dependencias con rango `^9.6` y sin `composer.lock`; `mbstring` no declarada; sin linter en dependencias de desarrollo | A06 Componentes vulnerables o desactualizados | Media | Declaré `ext-mbstring` y agregué phpcs, phpcs-security-audit y phpstan con scripts. **Falta** generar el `composer.lock` (P-01) | 47a01c2 (parcial) |
| H-05 | .gitignore, raíz | No existía `.env.example` y `.gitignore` no cubría `.env.*` ni `.phpunit.cache/` | A05 Configuración de seguridad | Baja | `.gitignore` ampliado y `.env.example` sin valores | f879a52 |
| H-06 | src/RegistroVehiculos.php:13-31 | La placa no tiene longitud máxima (acepté una de 1 MB) ni formato: «ТХУ123» con letras cirílicas o en ancho completo cuenta como otra placa | A04 Diseño inseguro / validación de entrada | Media | **Sin corregir**: el cliente no definió el formato (P-04) | pendiente |
| H-07 | src/RegistroVehiculos.php:13 | Marca, modelo y año no se validan (acepta año -5 y `<script>` en la marca) | A03 Inyección (si luego se muestra sin escapar) | Baja | **Sin corregir**: decisión de la semana 5, el cliente no dio reglas (P-04) | pendiente |

**Severidad:** alta (permite acceder o cambiar datos sin permiso, o expone secretos), media (error probable o riesgo con condiciones), baja (estilo o mantenibilidad).

### Avisos del linter no corregidos

| Regla | Archivo y línea | Por qué se mantiene |
|---|---|---|
| PSR1.Methods.CamelCapsMethodName | tests/RegistroVehiculosTest.php y tests/RegistroVehiculosAbusoTest.php (nombres de las pruebas) | Los nombres con guion bajo se leen como una frase y es una convención común en PHPUnit. Excluida solo en `tests/*` |
| PSR1.Classes.ClassDeclaration.MissingNamespace | tests/*.php | Las pruebas de la semana 5 no llevan namespace y la regla de la tarea es no modificarlas. PHPUnit las carga por archivo |

## 3. Revisión con IA

**Prompt usado:** «Actúa como revisor de código y de seguridad. Revisa estos archivos con los criterios de corrección y pruebas, diseño y legibilidad, y seguridad (inyección, secretos, contraseñas, control de acceso, dependencias). Devuelve una tabla con archivo y línea, problema y severidad, y separa los problemas seguros de las sospechas.» Entrada 7 de la bitácora. (Reconstruido a partir del enunciado de la tarea; si la OVA trae un prompt con otro texto, hay que cambiarlo aquí.)

| Hallazgo de la IA | ¿Real o falso positivo? | Cómo lo verifiqué |
|---|---|---|
| `normalizarPlaca` no limpia espacios Unicode (línea 41) | Real (H-01) | Script con «TXY123» + U+00A0 y U+200B: se guardaron como placas nuevas; luego 4 pruebas de abuso en rojo |
| Placa sin longitud máxima | Real (H-06) | Registré una placa de 1.000.000 de caracteres y se aceptó |
| Placa con letras parecidas (cirílico, ancho completo) cuenta como otra | Real (H-06) | Registré «ТХУ123» y «ＴＸＹ１２３»: se guardaron |
| Marca, modelo y año sin validación | Real, severidad baja (H-07) | Se guardó año -5 y `<script>alert(1)</script>` como marca |
| Autoload de `bootstrap.php` permite path traversal con `App\..\..\etc\passwd` (sospecha) | Falso positivo en cuanto a explotación | `class_exists('App\..\..\etc\passwd')` devolvió `false`: PHP no llama al autoload con nombres de clase inválidos. Igual lo reemplacé por Composer (H-03) |
| El año acepta texto como «2020abc» (sospecha) | Falso positivo | Lanza `TypeError`: PHP 8 no convierte ese texto a entero |
| Condición de carrera al revisar «placa repetida» y luego guardar (sospecha) | Falso positivo hoy | Es un arreglo en memoria y una sola ejecución. Será real con PostgreSQL: ahí manda la restricción UNIQUE (P-03) |
| Inyección SQL (sospecha) | Falso positivo | No hay base de datos ni consultas |

**Lo que la IA no encontró:** la IA no pudo ejecutar `composer install` ni `composer audit` (sin acceso a Packagist), así que no vio las versiones reales de las dependencias ni sus vulnerabilidades, y tampoco puede generar el `composer.lock`. Eso lo tengo que correr yo en mi computador (P-01).

## 4. Pendientes

| Pendiente | Por qué no se corrigió todavía | Plan |
|---|---|---|
| P-01. `composer.lock` y `composer audit` | El entorno de la revisión no llega a Packagist | `composer update`, subir `composer.lock` y correr `composer audit`; corregir lo que salga |
| P-02. Control de acceso por rol (HU-06, RNF-03) | Esta clase no tiene usuarios ni HTTP | En la API Laravel: middleware de rol y prueba de abuso (un pasajero recibe 403 al registrar un vehículo) |
| P-03. Inyección y unicidad con PostgreSQL | Todavía no hay base de datos | Consultas con parámetros (Eloquent o `?`), columna `placa` con UNIQUE y prueba de inyección |
| P-04. Formato y longitud de la placa, y reglas de marca, modelo y año (H-06, H-07) | El cliente no las ha definido | Preguntar a don Hernán; luego validar con una expresión como `^[A-Z0-9]{5,7}$` (422) y agregar pruebas |

**Claves expuestas:** Ninguna. No hay claves en el código ni en el historial de Git.
