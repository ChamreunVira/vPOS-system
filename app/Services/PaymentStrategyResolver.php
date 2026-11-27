<?php

namespace App\Services;

use App\Contracts\PaymentProcessor;
use App\Strategies\CardPaymentStrategy;
use App\Strategies\CashPaymentStrategy;
use App\Strategies\KhqrPaymentStrategy;
use InvalidArgumentException;

class PaymentStrategyResolver
{
    public function resolver(string $method): PaymentProcessor
    {
        return match ($method) {
            'cash' => app(CashPaymentStrategy::class),
            'khqr' => app(KhqrPaymentStrategy::class),
            'card' => app(CardPaymentStrategy::class),
            default => throw new InvalidArgumentException("Unsupported payment method: {$method}"),
        };
    }
}
