<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::Pending->value,
            self::Received->value,
            self::Cancelled->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }
}
