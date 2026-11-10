<?php

namespace App\Enums;

enum SoftDeleteStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function all()
    {
        return [
            SoftDeleteStatus::Active->value,
            SoftDeleteStatus::Inactive->value
        ];
    }
}
