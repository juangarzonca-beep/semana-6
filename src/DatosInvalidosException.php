<?php

declare(strict_types=1);

namespace App;

/**
 * Error de datos que no cumplen las reglas del negocio. La API lo convertirá en un 422.
 */
class DatosInvalidosException extends \InvalidArgumentException
{
}
