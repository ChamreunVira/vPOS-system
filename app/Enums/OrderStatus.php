<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::Pending->value,
            self::Completed->value,
            self::PartiallyRefunded->value,
            self::Refunded->value,
            self::Cancelled->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }
}
