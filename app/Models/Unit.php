<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'abbreviation', 'type', 'allow_decimal', 'status'])]
class Unit extends Model
{
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }
}
