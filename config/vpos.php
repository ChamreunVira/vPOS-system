<?php

return [
    'name' => env('VPOS_NAME', 'vPOS'),
    'store' => env('VPOS_STORE', 'Sala Mini Mart'),
    'register' => 'Register 01',

    // Primary currency is USD; KHR is shown as a secondary amount.
    'currency' => 'USD',
    'exchange_rate' => (int) env('VPOS_EXCHANGE_RATE', 4100), // 1 USD = 4,100 KHR
    'vat_rate' => (float) env('VPOS_VAT_RATE', 10),            // percent
    'discount_options' => [0, 5, 10, 15],
    'payment_methods' => ['cash', 'khqr', 'card'],
];
