<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PumpState extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'mode',
        'is_on',
        'last_changed_at',
        'last_command_source',
        'last_reason',
    ];

    protected $casts = [
        'is_on' => 'boolean',
        'last_changed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PumpLog::class);
    }
}
