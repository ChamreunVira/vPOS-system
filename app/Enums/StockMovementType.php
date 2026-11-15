<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public static function all(): array
    {
        return [
            self::In->value,
            self::Out->value,
            self::Adjustment->value,
        ];
    }
}
