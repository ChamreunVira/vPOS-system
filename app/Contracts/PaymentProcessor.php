<?php

namespace App\Contracts;

interface PaymentProcessor
{
    public function process(float $total, array $data): array;
}
