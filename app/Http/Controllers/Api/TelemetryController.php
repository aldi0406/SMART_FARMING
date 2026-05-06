<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesPumpState;
use App\Http\Controllers\Controller;
use App\Models\Reading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TelemetryController extends Controller
{
    use HandlesPumpState;

    public function store(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromRequest($request);

        if (! $device) {
            return response()->json([
                'message' => 'Token perangkat tidak valid atau perangkat nonaktif.',
            ], 401);
        }

        $validated = $request->validate([
            'soil_moisture' => ['required', 'numeric', 'between:0,100'],
            'air_temperature' => ['nullable', 'numeric', 'between:-20,80'],
            'air_humidity' => ['nullable', 'numeric', 'between:0,100'],
            'pump_state' => ['nullable', 'boolean'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $soilMoisture = (float) $validated['soil_moisture'];
        $airTemperature = array_key_exists('air_temperature', $validated)
            ? (float) $validated['air_temperature']
            : null;
        $airHumidity = array_key_exists('air_humidity', $validated)
            ? (float) $validated['air_humidity']
            : null;

        $pumpState = $this->ensurePumpState(
            $device,
            array_key_exists('pump_state', $validated)
                ? (bool) $validated['pump_state']
                : null
        );

        if (array_key_exists('pump_state', $validated)) {
            $this->syncFromDeviceReport($pumpState, (bool) $validated['pump_state']);
        }

        $pumpAction = [
            'action' => 'no_change',
            'applied' => false,
            'wait_seconds' => 0,
        ];

        if ($pumpState->mode === 'automatic') {
            $pumpAction = $this->applyAutomaticRule($pumpState, $soilMoisture);
        }

        $reading = Reading::query()->create([
            'device_id' => $device->id,
            'soil_moisture' => $soilMoisture,
            'air_temperature' => $airTemperature,
            'air_humidity' => $airHumidity,
            'soil_status' => $this->soilStatus($soilMoisture),
            'temperature_status' => $this->temperatureStatus($airTemperature),
            'humidity_status' => $this->humidityStatus($airHumidity),
            'pump_state' => (bool) $pumpState->is_on,
            'recorded_at' => isset($validated['recorded_at'])
                ? Carbon::parse($validated['recorded_at'])
                : now(),
            'raw_payload' => $request->all(),
        ]);

        $device->forceFill(['last_seen_at' => now()])->save();

        $this->broadcastDashboardUpdate($device, $pumpState);

        return response()->json([
            'message' => 'Telemetry berhasil disimpan.',
            'data' => [
                'reading_id' => $reading->id,
                'device_id' => $device->device_uid,
                'soil_status' => $reading->soil_status,
                'temperature_status' => $reading->temperature_status,
                'humidity_status' => $reading->humidity_status,
                'pump_mode' => $pumpState->mode,
                'pump_state' => (bool) $pumpState->is_on,
                'pump_action' => $pumpAction['action'] ?? 'no_change',
                'cooldown_wait_seconds' => $pumpAction['wait_seconds'] ?? 0,
                'recorded_at' => optional($reading->recorded_at)->toISOString(),
            ],
        ], 201);
    }

    private function soilStatus(float $soilMoisture): string
    {
        if ($soilMoisture < 40) {
            return 'low';
        }

        if ($soilMoisture <= 70) {
            return 'moderate';
        }

        return 'optimum';
    }

    private function temperatureStatus(?float $temperature): string
    {
        if ($temperature === null) {
            return 'unknown';
        }

        if ($temperature < 20) {
            return 'low';
        }

        if ($temperature <= 30) {
            return 'normal';
        }

        return 'high';
    }

    private function humidityStatus(?float $humidity): string
    {
        if ($humidity === null) {
            return 'unknown';
        }

        if ($humidity < 40) {
            return 'low';
        }

        if ($humidity <= 70) {
            return 'normal';
        }

        return 'high';
    }
}
