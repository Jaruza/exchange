<?php

namespace App\Services;

/**
 * Lógica de conversión entre pesos chilenos (CLP) y dólares (USD).
 *
 * La tasa es fija: 950 CLP equivalen a 1 USD.
 */
class CurrencyConverterService
{
    /**
     * Cantidad de pesos chilenos que equivalen a 1 dólar.
     */
    public const EXCHANGE_RATE = 950;

    /**
     * Devuelve la tasa de cambio vigente.
     */
    public function getExchangeRate(): int
    {
        return self::EXCHANGE_RATE;
    }

    /**
     * Convierte pesos chilenos a dólares, redondeando a 2 decimales (centavos).
     */
    public function clpToUsd(int $amountClp): float
    {
        return round($amountClp / self::EXCHANGE_RATE, 2);
    }

    /**
     * Convierte dólares a pesos chilenos, redondeando a pesos enteros.
     */
    public function usdToClp(float $amountUsd): int
    {
        return (int) round($amountUsd * self::EXCHANGE_RATE);
    }
}
