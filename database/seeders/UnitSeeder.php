<?php

namespace Database\Seeders;

use App\Enums\SoftDeleteStatus;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Piece',
                'abbreviation' => 'pc',
                'type' => 'Count',
                'allow_decimal' => false,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Kilogram',
                'abbreviation' => 'kg',
                'type' => 'Weight',
                'allow_decimal' => true,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Gram',
                'abbreviation' => 'g',
                'type' => 'Weight',
                'allow_decimal' => true,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Liter',
                'abbreviation' => 'L',
                'type' => 'Volume',
                'allow_decimal' => true,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Milliliter',
                'abbreviation' => 'ml',
                'type' => 'Volume',
                'allow_decimal' => true,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Pack',
                'abbreviation' => 'pk',
                'type' => 'Packaging',
                'allow_decimal' => false,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Box',
                'abbreviation' => 'box',
                'type' => 'Packaging',
                'allow_decimal' => false,
                'status' => SoftDeleteStatus::Active->value,
            ],
            [
                'name' => 'Bottle',
                'abbreviation' => 'btl',
                'type' => 'Packaging',
                'allow_decimal' => false,
                'status' => SoftDeleteStatus::Inactive->value,
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                $unit
            );
        }
    }
}
