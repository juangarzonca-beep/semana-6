<?php

declare(strict_types=1);

use App\DatosInvalidosException;
use App\RegistroVehiculos;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de abuso (semana 6): entradas hechas a propósito para saltarse la regla
 * «la placa es obligatoria y no puede repetirse» (HU-03, N-10).
 */
class RegistroVehiculosAbusoTest extends TestCase
{
    private const MENSAJE_ERROR = 'La placa es obligatoria y no puede repetirse';

    public function test_espacio_de_no_separacion_al_final_cuenta_como_placa_repetida(): void
    {
        $registro = new RegistroVehiculos();
        $registro->registrar('TXY123', 'Renault', 'Logan', 2020);

        $this->expectException(DatosInvalidosException::class);
        $this->expectExceptionMessage(self::MENSAJE_ERROR);

        $registro->registrar("TXY123\u{00A0}", 'Mazda', '3', 2018);
    }

    public function test_espacio_de_ancho_cero_al_final_cuenta_como_placa_repetida(): void
    {
        $registro = new RegistroVehiculos();
        $registro->registrar('TXY123', 'Renault', 'Logan', 2020);

        $this->expectException(DatosInvalidosException::class);

        $registro->registrar("TXY123\u{200B}", 'Mazda', '3', 2018);
    }

    public function test_una_placa_de_solo_espacios_de_no_separacion_cuenta_como_vacia(): void
    {
        $registro = new RegistroVehiculos();

        try {
            $registro->registrar("\u{00A0}\u{00A0}", 'Renault', 'Logan', 2020);
            $this->fail('Debió lanzar DatosInvalidosException');
        } catch (DatosInvalidosException $e) {
            $this->assertSame(self::MENSAJE_ERROR, $e->getMessage());
        }

        $this->assertCount(0, $registro->listar());
    }

    public function test_una_placa_con_texto_que_no_es_utf8_se_rechaza_y_no_se_guarda(): void
    {
        $registro = new RegistroVehiculos();

        try {
            $registro->registrar("\xff\xfe", 'Renault', 'Logan', 2020);
            $this->fail('Debió lanzar DatosInvalidosException');
        } catch (DatosInvalidosException $e) {
            $this->assertSame(self::MENSAJE_ERROR, $e->getMessage());
        }

        $this->assertCount(0, $registro->listar());
    }
}
