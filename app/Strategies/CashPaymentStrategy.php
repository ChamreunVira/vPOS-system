<?php

namespace App\Strategies;

use App\Contracts\PaymentProcessor;
use App\Helper\CartCalculator;
use Illuminate\Validation\ValidationException;
use Override;

class CashPaymentStrategy implements PaymentProcessor
{
    #[Override]
    public function process(float $total, array $data): array
    {
        $received = round((float) ($data['amount_received'] ?? 0), 2);

        if ($received < $total) {
            throw ValidationException::withMessages([
                'amount_received' => 'The amount received must cover the total.',
            ]);
        }

        return [
            'status' => 'paid',
            'amount' => $total,
            'received' => $received,
            'change' => CartCalculator::change($received, $total),
            'reference' => null,
            'paid_at' => now(),
        ];
    }
}
