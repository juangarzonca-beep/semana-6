# Registro de vehículos (cooperativa de taxis)

## 1. Qué es

Proyecto del Seminario de IA aplicada al ciclo de vida del software (FET). Es una parte pequeña de mi
proyecto final, la API de la app tipo Uber (Laravel, JWT y PostgreSQL): la función de **registrar
vehículos** (HU-03), hecha con pruebas primero. No usa Laravel ni base de datos: los vehículos se guardan
en una lista en memoria, y más adelante esa lista se cambia por PostgreSQL.

### Historia de usuario

**HU-03. Registrar un vehículo** (prioridad Must, necesidades N-04 y N-10)

Como administrador, quiero registrar un vehículo con su placa, marca, modelo y año para tener la
flota completa en un solo lugar.

Criterios de aceptación:

- **Escenario 1 (registro correcto):** dado que no existe la placa «TXY123», cuando registro placa
  «TXY123», marca «Renault», modelo «Logan» y año 2020, entonces el vehículo queda guardado con
  estado «disponible».
- **Escenario 2 (error: placa repetida o vacía):** dado que ya existe un vehículo con placa
  «TXY123», cuando intento registrar otro con la misma placa, o uno con la placa vacía, entonces el
  sistema responde con el mensaje «La placa es obligatoria y no puede repetirse» y no guarda el
  vehículo. (En la API este error sería un 422.)

### Contrato de la función

Clase: `App\RegistroVehiculos` (archivo `src/RegistroVehiculos.php`)

```php
public function registrar(string $placa, string $marca, string $modelo, int $anio): array
public function listar(): array
```

**Qué recibe `registrar`:** la placa, la marca, el modelo (texto) y el año (número entero).

**Qué devuelve:** un arreglo con los datos del vehículo guardado:
`['placa' => string, 'marca' => string, 'modelo' => string, 'anio' => int, 'estado' => 'disponible']`.

**Qué lanza:** `App\DatosInvalidosException` con el mensaje
«La placa es obligatoria y no puede repetirse» si la placa está vacía (incluida la que solo tiene espacios
Unicode o caracteres invisibles), no es texto UTF-8 válido o ya existe.
Cuando lanza el error no se guarda nada.

**`listar`:** devuelve todos los vehículos guardados, en el orden en que se registraron.

**Supuestos:**

- Todo vehículo nuevo empieza con estado «disponible» (así lo dice la historia).
- La placa se guarda en mayúsculas y sin espacios (o caracteres invisibles) a los lados. Lo supuse para que «txy123» y
  «TXY123» no cuenten como placas distintas (el cliente dijo que no quiere dos carros con la misma
  placa). Hay que confirmarlo con el cliente.
- Una placa de solo espacios cuenta como vacía.
- Del año, la marca y el modelo el cliente no dijo ninguna regla, así que por ahora no se validan.

**Casos límite:**

- Registro vacío: el primer vehículo se guarda sin problema.
- Placa con espacios o en minúsculas: se guarda limpia y en mayúsculas.
- Placa de solo espacios: error, igual que la placa vacía.
- Placa con espacio de no separación o de ancho cero al final: se limpia, así que cuenta como repetida
  (hallazgo H-01 de la semana 6).
- Misma placa escrita distinto («txy123» contra «TXY123»): error por repetida.

**Ejemplos:**

| Entrada | Salida |
|---|---|
| `registrar('TXY123', 'Renault', 'Logan', 2020)` | `['placa' => 'TXY123', 'marca' => 'Renault', 'modelo' => 'Logan', 'anio' => 2020, 'estado' => 'disponible']` |
| `registrar('  txy123 ', 'Renault', 'Logan', 2020)` | el vehículo se guarda con placa `'TXY123'` |
| `registrar('', 'Renault', 'Logan', 2020)` | lanza `DatosInvalidosException`: «La placa es obligatoria y no puede repetirse» |
| `registrar('TXY123', ...)` cuando `TXY123` ya existe | lanza `DatosInvalidosException`, y `listar()` sigue con 1 vehículo |

## 2. Requisitos

- PHP 8.1 o más, con la extensión `mbstring`.
- [Composer](https://getcomposer.org/) 2.

## 3. Instalación

```bash
git clone https://github.com/juangarzonca-beep/SEMINARIO.git
cd SEMINARIO
composer install
```

No hace falta un archivo `.env` para esta versión. El archivo `.env.example` muestra las variables que se
usarán cuando la lógica pase a la API (base de datos y clave JWT); el `.env` real no se sube a Git.

## 4. Ejecución

Es una clase, no un programa con pantalla. Un ejemplo rápido desde la terminal:

```bash
php -r 'require "vendor/autoload.php"; $r = new App\RegistroVehiculos(); print_r($r->registrar("  txy123 ", "Renault", "Logan", 2020));'
```

Imprime el vehículo con la placa `TXY123` y el estado `disponible`.

## 5. Pruebas

```bash
vendor/bin/phpunit        # o: composer test
```

Salen 12 pruebas: las 8 de la semana 5 (2 normales, 4 de límite y 2 de error) y 4 pruebas de abuso de la
semana 6 (`tests/RegistroVehiculosAbusoTest.php`: espacios Unicode, caracteres invisibles y texto que no es
UTF-8).

Revisión de calidad y seguridad:

```bash
composer lint     # PHP_CodeSniffer: estilo PSR-12 y reglas de seguridad (phpcs.xml)
composer stan     # PHPStan nivel máximo (phpstan.neon)
composer audit    # vulnerabilidades conocidas en las dependencias
```

El informe de la revisión está en [`revision_seguridad.md`](revision_seguridad.md).

## 6. Estructura

```
src/                   código de la funcionalidad (RegistroVehiculos, DatosInvalidosException)
tests/                 pruebas con PHPUnit (normales, de límite, de error y de abuso)
phpcs.xml              reglas del linter (PSR-12 + phpcs-security-audit)
phpstan.neon           configuración del análisis estático
.env.example           variables de entorno futuras, sin valores
revision_seguridad.md  informe de la revisión de código y de seguridad (semana 6)
bitacora_ia.md         registro de lo que sugirió la IA y qué decidí
```

## 7. Uso de IA

Semana 5: GitHub Copilot en Visual Studio Code (casos de prueba, primera versión de la clase y refactor).
Semana 6: Claude (chat) como segunda revisión del código; cada hallazgo se verificó ejecutando el código
antes de aceptarlo o descartarlo. Todo está en [`bitacora_ia.md`](bitacora_ia.md).

## 8. Autor y licencia

Juan José Garzón Cárdenas, estudiante de la Fundación Escuela Tecnológica de Neiva (FET).
Proyecto académico, sin licencia de código abierto por ahora.
