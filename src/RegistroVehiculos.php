<?php

declare(strict_types=1);

namespace App;

/**
 * Registro de vehículos de la cooperativa (HU-03).
 *
 * Guarda los vehículos en memoria, con la placa como llave. Más adelante la lista se cambia
 * por PostgreSQL, donde la placa tendrá una restricción UNIQUE.
 *
 * @phpstan-type Vehiculo array{placa: string, marca: string, modelo: string, anio: int, estado: string}
 */
class RegistroVehiculos
{
    private const ESTADO_INICIAL = 'disponible';
    private const MENSAJE_PLACA_INVALIDA = 'La placa es obligatoria y no puede repetirse';

    // Los vehículos se guardan con la placa como llave, así buscar una placa repetida es directo
    /** @var array<string, Vehiculo> */
    private array $vehiculos = [];

    /**
     * Registra un vehículo nuevo con estado «disponible».
     *
     * La placa se guarda sin espacios a los lados y en mayúsculas. Marca, modelo y año no se
     * validan porque el cliente no definió reglas para ellos.
     *
     * @param string $placa  Placa del vehículo (obligatoria y única, sin distinguir mayúsculas).
     * @param string $marca  Marca, por ejemplo «Renault».
     * @param string $modelo Modelo, por ejemplo «Logan».
     * @param int    $anio   Año del modelo, por ejemplo 2020.
     *
     * @return Vehiculo Los datos del vehículo guardado.
     *
     * @throws DatosInvalidosException Si la placa queda vacía, no es texto UTF-8 válido o ya existe.
     *                                 Cuando lanza el error no se guarda nada.
     */
    public function registrar(string $placa, string $marca, string $modelo, int $anio): array
    {
        $placa = $this->normalizarPlaca($placa);

        if (!$this->placaSePuedeUsar($placa)) {
            throw new DatosInvalidosException(self::MENSAJE_PLACA_INVALIDA);
        }

        $vehiculo = [
            'placa' => $placa,
            'marca' => $marca,
            'modelo' => $modelo,
            'anio' => $anio,
            'estado' => self::ESTADO_INICIAL,
        ];
        $this->vehiculos[$placa] = $vehiculo;

        return $vehiculo;
    }

    /**
     * Devuelve todos los vehículos guardados, en el orden en que se registraron.
     *
     * @return list<Vehiculo> Lista vacía si todavía no hay vehículos.
     */
    public function listar(): array
    {
        return array_values($this->vehiculos);
    }

    // Quita espacios a los lados (incluidos los de Unicode, como el de no separación) y pasa a
    // mayúsculas, así «txy123» y «TXY123» son la misma placa
    /**
     * @throws DatosInvalidosException Si el texto de la placa no es UTF-8 válido.
     */
    private function normalizarPlaca(string $placa): string
    {
        // \p{Z}: espacios Unicode (U+00A0, etc.). \p{C}: caracteres invisibles o de control (U+200B, BOM)
        $limpia = preg_replace('/^[\s\p{Z}\p{C}]+|[\s\p{Z}\p{C}]+$/u', '', $placa);

        // preg_replace devuelve null cuando el texto no es UTF-8 válido
        if ($limpia === null) {
            throw new DatosInvalidosException(self::MENSAJE_PLACA_INVALIDA);
        }

        return mb_strtoupper($limpia, 'UTF-8');
    }

    // La placa sirve si no está vacía y todavía no existe
    private function placaSePuedeUsar(string $placa): bool
    {
        return $placa !== '' && !isset($this->vehiculos[$placa]);
    }
}
