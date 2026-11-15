<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Return = 'return';
    case Adjustment = 'adjustment';
    case Damage = 'damage';
    case In = 'in';
    case Out = 'out';

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::Purchase->value,
            self::Sale->value,
            self::Return->value,
            self::Adjustment->value,
            self::Damage->value,
            self::In->value,
            self::Out->value,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Return => 'Return',
            self::Adjustment => 'Adjustment',
            self::Damage => 'Damage',
            self::In => 'Stock In',
            self::Out => 'Stock Out',
        };
    }
}
