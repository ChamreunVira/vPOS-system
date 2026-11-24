<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'pairing_token_hash', 'device_token_hash', 'pairing_code_hash', 'paired_at', 'expires_at'])]
class PosScanSession extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paired_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PosScanEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PosScanEvent::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPaired(): bool
    {
        return $this->paired_at !== null && $this->device_token_hash !== null && ! $this->isExpired();
    }
}
