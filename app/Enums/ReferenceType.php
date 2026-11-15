<?php

namespace App\Enums;

enum ReferenceType: string
{
    case Purchase = "purchase";
    case Sale = "sale";
    case Adjustment = "adjustment";

    public static function all(): array
    {
        return [
            self::Purchase->value,
            self::Sale->value,
            self::Adjustment->value,
        ];
    }
}
