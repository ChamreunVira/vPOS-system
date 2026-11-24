<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pos_scan_session_id', 'barcode', 'processed_at', 'created_at'])]
class PosScanEvent extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PosScanSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PosScanSession::class, 'pos_scan_session_id');
    }
}
