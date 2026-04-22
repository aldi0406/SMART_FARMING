<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'device_uid',
        'api_token',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    public function pumpState(): HasOne
    {
        return $this->hasOne(PumpState::class);
    }

    public function pumpLogs(): HasMany
    {
        return $this->hasMany(PumpLog::class);
    }
}
