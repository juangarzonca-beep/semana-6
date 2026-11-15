# Bitácora de uso de IA

Proyecto: API de la app tipo Uber (Laravel, JWT y PostgreSQL). Aquí, el registro de vehículos en un repositorio aparte
Estudiante: Juan José Garzón Cárdenas
Funcionalidad: HU-03 Registrar un vehículo
Asistentes usados: GitHub Copilot en Visual Studio Code (semana 5) y Claude en el chat (semana 6, revisión)
Agente de código: no usé ninguno en la semana 6; los cambios los hice en la rama `semana-6-revision`, un commit por hallazgo

## Entradas

### Entrada 1

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Con esta historia HU-03 y sus dos escenarios, dame casos de prueba en PHPUnit para registrar un vehículo: normales, de límite y de error.»
- **Decisión:** Aceptada
- **Por qué:** Los casos cubrían los dos escenarios de mi criterio de aceptación (registro correcto y placa repetida o vacía) y además pensó en el registro vacío y en la placa con espacios, que yo no había anotado.
- **Verificación:** Leí cada prueba y comprobé que tuviera nombre claro y que el mensaje de error fuera el mismo de la historia. Con el código sin escribir las 8 pruebas fallaron todas, como debía ser (`phpunit`).
- **Commit relacionado:** 621f63f «Pruebas de registrar vehículo, HU-03 (en rojo)»

### Entrada 2

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** Copilot sugirió agregar pruebas para rechazar un año menor que 1900 o mayor que el año actual, y una marca o modelo vacíos.
- **Decisión:** Rechazada
- **Por qué:** El cliente no dijo ninguna regla para el año, la marca ni el modelo; en mi documento de requisitos solo piden la placa única y obligatoria. Si las agregaba, el código tendría reglas sin origen en las historias.
- **Verificación:** Revisé la HU-03 y el RNF-05 del documento de requisitos y busqué esas reglas: no aparecen. Dejé anotado en el README que esos campos por ahora no se validan.
- **Commit relacionado:** 621f63f «Pruebas de registrar vehículo, HU-03 (en rojo)»

### Entrada 3

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Con el contrato del README y estas pruebas, escribe la clase RegistroVehiculos.» Copilot comparó la placa con `empty($placa)` y buscó repetidas con `isset($this->vehiculos[$placa])`, sin tocar el texto de la placa.
- **Decisión:** Aceptada con cambios
- **Por qué:** La idea de guardar los vehículos con la placa como llave me sirvió, pero no limpiaba la placa: «  txy123 » se guardaba tal cual y «txy123» no se detectaba como repetida de «TXY123». Le agregué `strtoupper(trim($placa))` y cambié `empty()` por `=== ''`.
- **Verificación:** Al correr `phpunit` fallaron 3 de las 8 pruebas (mayúsculas y espacios, placa de solo espacios y placa repetida en minúsculas). Después del cambio pasaron las 8.
- **Commit relacionado:** 98ca753 «Registrar vehículo: implementación, las 8 pruebas pasan (en verde)»

### Entrada 4

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** Copilot propuso guardar los vehículos con un modelo Eloquent y la base de datos PostgreSQL desde el principio.
- **Decisión:** Rechazada
- **Por qué:** Esta tarea es una función pequeña y las pruebas deben correr con un solo comando, sin instalar Laravel ni conectar una base de datos. Lo de PostgreSQL ya está decidido en mi diseño (ADR-001), pero lo hago después, cuando esta lógica pase a la API.
- **Verificación:** Con una lista en memoria las pruebas corren con `vendor/bin/phpunit` y tardan menos de un segundo.
- **Commit relacionado:** 98ca753 «Registrar vehículo: implementación, las 8 pruebas pasan (en verde)»

### Entrada 5

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** «Refactoriza RegistroVehiculos sin cambiar su comportamiento: quita los valores mágicos y separa lo que se pueda en métodos pequeños.»
- **Decisión:** Aceptada
- **Por qué:** Dejó el estado inicial y el mensaje de error en constantes y sacó dos métodos privados (`normalizarPlaca` y `placaSePuedeUsar`). El método `registrar` ahora se lee casi como la historia.
- **Verificación:** No toqué ninguna prueba (`git diff` de la carpeta `tests` sale vacío) y siguen pasando las 8 pruebas.
- **Commit relacionado:** 7e9dff4 «Refactor: constantes para el estado y el mensaje, y métodos normalizarPlaca y placaSePuedeUsar»

### Entrada 6

- **Fecha:** 2026-11-07
- **Prompt o sugerencia:** Para el error de placa inválida, Copilot sugirió lanzar la `InvalidArgumentException` que ya trae PHP con el mensaje de texto.
- **Decisión:** Aceptada con cambios
- **Por qué:** Prefería una excepción propia, `DatosInvalidosException`, para que la API pueda convertirla en un 422 sin confundirla con otros errores de PHP. La hice heredar de `InvalidArgumentException`, así que sigue siendo una excepción de PHP.
- **Verificación:** Las pruebas de error usan `expectException(DatosInvalidosException::class)` y comprueban el mensaje exacto de la historia; pasan las dos.
- **Commit relacionado:** 98ca753 «Registrar vehículo: implementación, las 8 pruebas pasan (en verde)»

### Entrada 7

- **Fecha:** 2026-11-14
- **Prompt o sugerencia:** «Actúa como revisor de código y de seguridad. Revisa estos archivos con los criterios de corrección y pruebas, diseño y legibilidad, y seguridad (inyección, secretos, contraseñas, control de acceso, dependencias). Devuelve una tabla con archivo y línea, problema y severidad, y separa los problemas seguros de las sospechas.» (Claude, chat)
- **Decisión:** Aceptada con cambios
- **Por qué:** Dejé los 4 hallazgos que sí podía reproducir (espacios Unicode en la placa, placa sin longitud máxima, letras parecidas y campos sin validar) y descarté 4 sospechas: path traversal en el autoload, año como texto, condición de carrera e inyección SQL.
- **Verificación:** Probé cada caso con un script en PHP. Con «TXY123» más un espacio de no separación se guardó otra placa, y una placa de 1 MB se aceptó. En cambio `class_exists('App\..\..\etc\passwd')` dio `false` y el año «2020abc» lanzó `TypeError`. Todo quedó en la tabla 3 de `revision_seguridad.md`.
- **Commit relacionado:** 6d33361 «Informe revision_seguridad.md…»

### Entrada 8

- **Fecha:** 2026-11-14
- **Prompt o sugerencia:** Corregir `normalizarPlaca` para que limpie espacios Unicode e invisibles: `preg_replace` con `\p{Z}` y `\p{C}` en modo `/u`, `mb_strtoupper` y error si el texto no es UTF-8 (`preg_replace` devuelve `null`).
- **Decisión:** Aceptada
- **Por qué:** Es la regla que el cliente pidió (placa obligatoria y sin repetirse) aplicada a todas las formas de escribir un espacio, y usa el mismo mensaje de error de la historia.
- **Verificación:** Primero escribí 4 pruebas de abuso y fallaron las 4 (rojo). Después de la corrección pasan las 12 pruebas, y `git diff 94e701d -- tests/RegistroVehiculosTest.php` sale vacío.
- **Commit relacionado:** fbeaf66 (pruebas en rojo) y f53e449 (corrección)

### Entrada 9

- **Fecha:** 2026-11-14
- **Prompt o sugerencia:** Escribir docstrings (qué hace, qué recibe, qué devuelve, qué lanza) y tipos de arreglo en PHPDoc para los 3 errores de PHPStan.
- **Decisión:** Aceptada
- **Por qué:** PHPStan en nivel máximo pasó de 3 errores a ninguno y las funciones principales quedaron documentadas.
- **Verificación:** Comparé cada docstring con el código: `registrar` lanza solo `DatosInvalidosException`, `listar` devuelve lista vacía si no hay vehículos, y la placa se guarda limpia y en mayúsculas. Corrí `phpstan` y `phpunit`: sin errores y 12 pruebas pasando.
- **Commit relacionado:** c69db10

### Entrada 10

- **Fecha:** 2026-11-14
- **Prompt o sugerencia:** Validar la placa con el formato `^[A-Z]{3}[0-9]{3}$` y una longitud máxima, para cerrar las letras parecidas y la placa gigante.
- **Decisión:** Rechazada
- **Por qué:** El cliente no definió el formato (hay motos y otros tipos de placa). Si lo agregaba, el código tendría una regla sin origen en las historias, igual que en la Entrada 2. Quedó como pendiente P-04 para preguntarle a don Hernán.
- **Verificación:** Revisé HU-03 y el RNF-05: solo piden placa obligatoria y única. Anoté el riesgo en H-06 del informe.
- **Commit relacionado:** 6d33361 «Informe revision_seguridad.md…»

## Resumen

| Decisión | Cantidad |
|---|---|
| Aceptadas | 4 |
| Aceptadas con cambios | 3 |
| Rechazadas | 3 |

## Errores que atraparon las pruebas

La primera versión que sugirió Copilot comparaba la placa tal como llegaba. Tres pruebas fallaron:
`test_guarda_la_placa_en_mayusculas_y_sin_espacios_a_los_lados` (la placa quedaba como `'  txy123 '`),
`test_una_placa_con_solo_espacios_cuenta_como_vacia` (no lanzaba el error, porque `empty('   ')` es falso)
y `test_la_misma_placa_escrita_en_minusculas_cuenta_como_repetida` (dejaba registrar «txy123» aunque «TXY123»
ya existía). Lo corregí limpiando la placa con `trim` y `strtoupper` antes de validarla, y comparando con `=== ''`.
Sin esas pruebas habría quedado un código que parecía funcionar con el ejemplo de la historia, pero dejaba duplicar
placas con solo cambiar las mayúsculas, que es justo lo que el cliente no quiere.

## Errores que atrapó la revisión de la semana 6

Las 8 pruebas de la semana 5 pasaban, pero la limpieza de la placa solo conocía los espacios normales. Un espacio
de no separación (U+00A0) o uno de ancho cero (U+200B) al final dejaba registrar «TXY123» dos veces, y una placa de
solo esos espacios se aceptaba como si no estuviera vacía. Lo encontré probando entradas raras a propósito y lo
corregí con 4 pruebas de abuso escritas antes del arreglo.

## Reflexión final

Lo que más me ayudó Copilot fue a pensar casos que yo no tenía, como el registro vacío y la placa con espacios, y a
armar rápido la estructura de la clase. El error que atraparon mis pruebas fue que la primera versión no limpiaba la
placa, así que se podía repetir con minúsculas. Aprendí que el código que da la IA puede pasar el ejemplo de la
historia y aun así fallar en los casos límite, y que por eso conviene escribir las pruebas antes. También tuve que
rechazar sugerencias que se pasaban del contrato, como validar el año o usar la base de datos, porque nadie las había
pedido. En la próxima funcionalidad escribiría yo mismo la primera versión de los casos límite antes de pedirle
ayuda al asistente, y confirmaría con el cliente los supuestos (como lo de las mayúsculas) antes de programarlos.
