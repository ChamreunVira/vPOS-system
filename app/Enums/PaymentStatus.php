<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::Pending->value,
            self::Paid->value,
            self::Failed->value,
            self::PartiallyRefunded->value,
            self::Refunded->value,
        ];
    }
}
