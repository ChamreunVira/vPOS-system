<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Refunded = 'completed';
    case Cancelled = 'cancelled';
}
