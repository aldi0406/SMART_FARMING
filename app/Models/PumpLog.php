<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PumpLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'pump_state_id',
        'source',
        'action',
        'mode',
        'from_state',
        'to_state',
        'reason',
        'meta',
    ];

    protected $casts = [
        'from_state' => 'boolean',
        'to_state' => 'boolean',
        'meta' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function pumpState(): BelongsTo
    {
        return $this->belongsTo(PumpState::class);
    }
}
