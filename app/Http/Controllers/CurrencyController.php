<?php

namespace App\Http\Controllers;

use App\Services\CurrencyConverterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de la API de conversión CLP ↔ USD.
 *
 * La validación usa $request->validate(): si falla, Laravel lanza una
 * ValidationException que se transforma en JSON 422 en bootstrap/app.php.
 */
class CurrencyController extends Controller
{
    public function __construct(
        private readonly CurrencyConverterService $converter,
    ) {}

    /**
     * GET /api/exchange-rate
     */
    public function exchangeRate(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'from' => 'CLP',
            'to' => 'USD',
            'exchange_rate' => $this->converter->getExchangeRate(),
        ]);
    }

    /**
     * POST /api/convert — convierte CLP a USD.
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'amount' => ['bail', 'required', 'numeric', 'integer', 'min:1', 'max:100000000'],
            ],
            [
                'amount.required' => 'El campo amount es obligatorio.',
                'amount.numeric' => 'El monto debe ser numérico.',
                'amount.integer' => 'El monto debe ser un número entero de pesos chilenos.',
                'amount.min' => 'El monto debe ser mayor a cero.',
                'amount.max' => 'El monto no puede superar los $100.000.000 CLP.',
            ],
        );

        $amountClp = (int) $validated['amount'];

        return response()->json([
            'success' => true,
            'amount_clp' => $amountClp,
            'amount_usd' => $this->converter->clpToUsd($amountClp),
            'exchange_rate' => $this->converter->getExchangeRate(),
        ]);
    }

    /**
     * POST /api/convert/usd-to-clp — convierte USD a CLP.
     */
    public function usdToClp(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'amount' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:105263.15'],
            ],
            [
                'amount.required' => 'El campo amount es obligatorio.',
                'amount.numeric' => 'El monto debe ser numérico.',
                'amount.decimal' => 'El monto en dólares admite como máximo dos decimales.',
                'amount.min' => 'El monto debe ser mayor a cero.',
                'amount.max' => 'El monto no puede superar los US$ 105.263,15 (equivalente a $100.000.000 CLP).',
            ],
        );

        $amountUsd = (float) $validated['amount'];

        return response()->json([
            'success' => true,
            'amount_usd' => $amountUsd,
            'amount_clp' => $this->converter->usdToClp($amountUsd),
            'exchange_rate' => $this->converter->getExchangeRate(),
        ]);
    }
}
