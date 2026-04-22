<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Events\DashboardDataUpdated;
use App\Models\Device;
use App\Models\PumpLog;
use App\Models\PumpState;
use Illuminate\Http\Request;
use Throwable;

trait HandlesPumpState
{
    protected function resolveDeviceFromRequest(Request $request): ?Device
    {
        $token = (string) $request->header('X-Device-Token');

        if ($token === '') {
            return null;
        }

        return Device::query()
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    protected function ensurePumpState(Device $device, ?bool $initialState = null): PumpState
    {
        return PumpState::query()->firstOrCreate(
            ['device_id' => $device->id],
            [
                'mode' => 'automatic',
                'is_on' => (bool) ($initialState ?? false),
                'last_changed_at' => null,
                'last_command_source' => 'bootstrap',
                'last_reason' => 'initial_state',
            ]
        );
    }

    protected function syncFromDeviceReport(PumpState $pumpState, bool $reportedState): void
    {
        if ((bool) $pumpState->is_on === $reportedState) {
            return;
        }

        $fromState = (bool) $pumpState->is_on;

        $pumpState->forceFill([
            'is_on' => $reportedState,
            'last_changed_at' => now(),
            'last_command_source' => 'device_report',
            'last_reason' => 'state_reported_by_device',
        ])->save();

        $this->logPumpAction(
            $pumpState,
            'device_report',
            $reportedState ? 'switched_on' : 'switched_off',
            $fromState,
            $reportedState,
            'state_reported_by_device',
            []
        );
    }

    protected function applyAutomaticRule(PumpState $pumpState, float $soilMoisture): array
    {
        $onBelow = (float) config('smart_farming.pump.soil_moisture_on_below', 40);
        $offAbove = (float) config('smart_farming.pump.soil_moisture_off_above', 70);

        $desiredState = null;
        $reason = null;

        if (! $pumpState->is_on && $soilMoisture < $onBelow) {
            $desiredState = true;
            $reason = 'soil_moisture_below_threshold';
        }

        if ($pumpState->is_on && $soilMoisture > $offAbove) {
            $desiredState = false;
            $reason = 'soil_moisture_above_threshold';
        }

        if ($desiredState === null) {
            return [
                'applied' => false,
                'action' => 'no_change',
                'state' => (bool) $pumpState->is_on,
            ];
        }

        return $this->switchPumpState(
            $pumpState,
            $desiredState,
            'automatic_rule',
            $reason,
            [
                'soil_moisture' => $soilMoisture,
                'soil_moisture_on_below' => $onBelow,
                'soil_moisture_off_above' => $offAbove,
            ]
        );
    }

    protected function switchPumpState(
        PumpState $pumpState,
        bool $desiredState,
        string $source,
        string $reason,
        array $meta = []
    ): array {
        if ((bool) $pumpState->is_on === $desiredState) {
            return [
                'applied' => false,
                'action' => 'no_change',
                'state' => (bool) $pumpState->is_on,
            ];
        }

        $cooldownCheck = $this->checkCooldown($pumpState);

        if (! $cooldownCheck['allowed']) {
            return [
                'applied' => false,
                'action' => 'cooldown_blocked',
                'wait_seconds' => $cooldownCheck['wait_seconds'],
                'state' => (bool) $pumpState->is_on,
            ];
        }

        $fromState = (bool) $pumpState->is_on;

        $pumpState->forceFill([
            'is_on' => $desiredState,
            'last_changed_at' => now(),
            'last_command_source' => $source,
            'last_reason' => $reason,
        ])->save();

        $this->logPumpAction(
            $pumpState,
            $source,
            $desiredState ? 'switched_on' : 'switched_off',
            $fromState,
            $desiredState,
            $reason,
            $meta
        );

        return [
            'applied' => true,
            'action' => $desiredState ? 'switched_on' : 'switched_off',
            'state' => $desiredState,
            'wait_seconds' => 0,
        ];
    }

    protected function checkCooldown(PumpState $pumpState): array
    {
        $cooldownSeconds = (int) config('smart_farming.pump.cooldown_seconds', 60);

        if (! $pumpState->last_changed_at || $cooldownSeconds <= 0) {
            return [
                'allowed' => true,
                'wait_seconds' => 0,
            ];
        }

        $elapsed = $pumpState->last_changed_at->diffInSeconds(now());
        $waitSeconds = max($cooldownSeconds - $elapsed, 0);

        return [
            'allowed' => $waitSeconds === 0,
            'wait_seconds' => $waitSeconds,
        ];
    }

    protected function logPumpAction(
        PumpState $pumpState,
        string $source,
        string $action,
        ?bool $fromState,
        ?bool $toState,
        ?string $reason,
        array $meta
    ): void {
        PumpLog::query()->create([
            'device_id' => $pumpState->device_id,
            'pump_state_id' => $pumpState->id,
            'source' => $source,
            'action' => $action,
            'mode' => $pumpState->mode,
            'from_state' => $fromState,
            'to_state' => $toState,
            'reason' => $reason,
            'meta' => $meta,
        ]);
    }

    protected function getDashboardPayload(Device $device, ?PumpState $pumpState = null): array
    {
        $state = $pumpState ?? $this->ensurePumpState($device);

        $reading = $device->readings()
            ->latest('recorded_at')
            ->latest('id')
            ->first();

        return [
            'device_uid' => $device->device_uid,
            'soil_moisture' => $reading?->soil_moisture,
            'soil_status' => $reading?->soil_status,
            'air_temperature' => $reading?->air_temperature,
            'temperature_status' => $reading?->temperature_status,
            'air_humidity' => $reading?->air_humidity,
            'humidity_status' => $reading?->humidity_status,
            'pump_mode' => $state->mode,
            'pump_state' => (bool) $state->is_on,
            'recorded_at' => optional($reading?->recorded_at)->toISOString(),
            'last_changed_at' => optional($state->last_changed_at)->toISOString(),
        ];
    }

    protected function broadcastDashboardUpdate(Device $device, ?PumpState $pumpState = null): void
    {
        try {
            event(new DashboardDataUpdated(
                $this->getDashboardPayload($device, $pumpState)
            ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
