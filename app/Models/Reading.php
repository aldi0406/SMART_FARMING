<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reading extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'soil_moisture',
        'air_temperature',
        'air_humidity',
        'soil_status',
        'temperature_status',
        'humidity_status',
        'pump_state',
        'recorded_at',
        'raw_payload',
    ];

    protected $casts = [
        'soil_moisture' => 'decimal:2',
        'air_temperature' => 'decimal:2',
        'air_humidity' => 'decimal:2',
        'pump_state' => 'boolean',
        'recorded_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
