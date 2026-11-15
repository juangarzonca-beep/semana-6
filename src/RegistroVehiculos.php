<?php

namespace App;

class RegistroVehiculos
{
    private const ESTADO_INICIAL = 'disponible';
    private const MENSAJE_PLACA_INVALIDA = 'La placa es obligatoria y no puede repetirse';

    // Los vehículos se guardan con la placa como llave, así buscar una placa repetida es directo
    private array $vehiculos = [];

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

    public function listar(): array
    {
        return array_values($this->vehiculos);
    }

    // Quita espacios a los lados (incluidos los de Unicode, como el de no separación) y pasa a
    // mayúsculas, así «txy123» y «TXY123» son la misma placa
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
