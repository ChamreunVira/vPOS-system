<?php

namespace App\Strategies;

use App\Contracts\PaymentProcessor;
use Override;

class CardPaymentStrategy implements PaymentProcessor
{
    #[Override]
    public function process(float $total, array $data): array
    {
        return [
            'status' => 'pending',
            'amount' => $total,
            'received' => 0,
            'change' => 0,
            'reference' => null,
            'paid_at' => null,
        ];
    }
}
