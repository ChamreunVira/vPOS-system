<?php

namespace App\Enums;

enum PermissionAction: string
{
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Refund = 'refund';


    public static function all()
    {
        return [
            PermissionAction::View->value,
            PermissionAction::Create->value,
            PermissionAction::Update->value,
            PermissionAction::Delete->value
        ];
    }
}
