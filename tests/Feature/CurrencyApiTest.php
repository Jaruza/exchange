<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CurrencyApiTest extends TestCase
{
    // ---------------------------------------------------------------
    // GET /api/exchange-rate
    // ---------------------------------------------------------------

    public function test_exchange_rate_returns_fixed_rate(): void
    {
        $this->getJson('/api/exchange-rate')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'from' => 'CLP',
                'to' => 'USD',
                'exchange_rate' => 950,
            ]);
    }

    // ---------------------------------------------------------------
    // POST /api/convert (CLP → USD)
    // ---------------------------------------------------------------

    public static function validClpAmounts(): array
    {
        return [
            '19000 CLP son 20 USD' => [19000, 20],
            '1000 CLP son 1.05 USD' => [1000, 1.05],
            '950 CLP son 1 USD' => [950, 1],
        ];
    }

    #[DataProvider('validClpAmounts')]
    public function test_convert_clp_to_usd(int $amountClp, float|int $expectedUsd): void
    {
        $this->postJson('/api/convert', ['amount' => $amountClp])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'amount_clp' => $amountClp,
                'amount_usd' => $expectedUsd,
                'exchange_rate' => 950,
            ]);
    }

    public static function invalidClpPayloads(): array
    {
        return [
            'monto cero' => [['amount' => 0], 'El monto debe ser mayor a cero.'],
            'monto negativo' => [['amount' => -5000], 'El monto debe ser mayor a cero.'],
            'texto' => [['amount' => 'hola'], 'El monto debe ser numérico.'],
            'decimal' => [['amount' => 1500.50], 'El monto debe ser un número entero de pesos chilenos.'],
            'sobre el máximo' => [['amount' => 100000001], 'El monto no puede superar los $100.000.000 CLP.'],
            'campo ausente' => [[], 'El campo amount es obligatorio.'],
        ];
    }

    #[DataProvider('invalidClpPayloads')]
    public function test_convert_clp_to_usd_rejects_invalid_amounts(array $payload, string $message): void
    {
        $this->postJson('/api/convert', $payload)
            ->assertUnprocessable()
            ->assertExactJson([
                'success' => false,
                'message' => $message,
            ]);
    }

    // ---------------------------------------------------------------
    // POST /api/convert/usd-to-clp (USD → CLP)
    // ---------------------------------------------------------------

    public static function validUsdAmounts(): array
    {
        return [
            '20 USD son 19000 CLP' => [20, 19000],
            '1 USD son 950 CLP' => [1, 950],
            '1.05 USD son 998 CLP' => [1.05, 998],
        ];
    }

    #[DataProvider('validUsdAmounts')]
    public function test_convert_usd_to_clp(float|int $amountUsd, int $expectedClp): void
    {
        $this->postJson('/api/convert/usd-to-clp', ['amount' => $amountUsd])
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'amount_usd' => $amountUsd,
                'amount_clp' => $expectedClp,
                'exchange_rate' => 950,
            ]);
    }

    public static function invalidUsdPayloads(): array
    {
        return [
            'monto cero' => [['amount' => 0], 'El monto debe ser mayor a cero.'],
            'monto negativo' => [['amount' => -5], 'El monto debe ser mayor a cero.'],
            'texto' => [['amount' => 'hola'], 'El monto debe ser numérico.'],
            'tres decimales' => [['amount' => 1.234], 'El monto en dólares admite como máximo dos decimales.'],
            'sobre el máximo' => [['amount' => 105263.16], 'El monto no puede superar los US$ 105.263,15 (equivalente a $100.000.000 CLP).'],
            'campo ausente' => [[], 'El campo amount es obligatorio.'],
        ];
    }

    #[DataProvider('invalidUsdPayloads')]
    public function test_convert_usd_to_clp_rejects_invalid_amounts(array $payload, string $message): void
    {
        $this->postJson('/api/convert/usd-to-clp', $payload)
            ->assertUnprocessable()
            ->assertExactJson([
                'success' => false,
                'message' => $message,
            ]);
    }

    // ---------------------------------------------------------------
    // Errores HTTP en JSON
    // ---------------------------------------------------------------

    public function test_wrong_method_returns_json_405(): void
    {
        $this->get('/api/convert')
            ->assertMethodNotAllowed()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'success' => false,
                'message' => 'Método HTTP no permitido para esta ruta.',
            ]);
    }

    public function test_unknown_route_returns_json_404(): void
    {
        $this->get('/api/no-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'success' => false,
                'message' => 'La ruta solicitada no existe.',
            ]);
    }
}
