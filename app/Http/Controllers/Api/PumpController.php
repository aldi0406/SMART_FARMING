<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesPumpState;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PumpController extends Controller
{
    use HandlesPumpState;

    public function state(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromRequest($request);

        if (! $device) {
            return response()->json([
                'message' => 'Token perangkat tidak valid atau perangkat nonaktif.',
            ], 401);
        }

        $pumpState = $this->ensurePumpState($device);

        return response()->json([
            'message' => 'Status pompa berhasil diambil.',
            'data' => [
                'device_id' => $device->device_uid,
                'mode' => $pumpState->mode,
                'is_on' => (bool) $pumpState->is_on,
                'last_changed_at' => optional($pumpState->last_changed_at)->toISOString(),
                'last_command_source' => $pumpState->last_command_source,
                'last_reason' => $pumpState->last_reason,
            ],
        ]);
    }

    public function command(Request $request): JsonResponse
    {
        $device = $this->resolveDeviceFromRequest($request);

        if (! $device) {
            return response()->json([
                'message' => 'Token perangkat tidak valid atau perangkat nonaktif.',
            ], 401);
        }

        $validated = $request->validate([
            'mode' => ['nullable', 'in:manual,automatic'],
            'pump_state' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:150'],
        ]);

        $pumpState = $this->ensurePumpState($device);

        $targetMode = $validated['mode'] ?? $pumpState->mode;

        if ($targetMode === 'manual' && ! array_key_exists('pump_state', $validated)) {
            return response()->json([
                'message' => 'Field pump_state wajib saat mode manual.',
            ], 422);
        }

        if ($targetMode === 'automatic' && array_key_exists('pump_state', $validated)) {
            return response()->json([
                'message' => 'Field pump_state hanya boleh dikirim saat mode manual.',
            ], 422);
        }

        $modeChanged = false;

        if ($targetMode !== $pumpState->mode) {
            $previousMode = $pumpState->mode;
            $pumpState->mode = $targetMode;
            $pumpState->save();

            $modeChanged = true;

            $this->logPumpAction(
                $pumpState,
                'manual_api',
                'mode_changed',
                (bool) $pumpState->is_on,
                (bool) $pumpState->is_on,
                'mode_changed_by_api',
                [
                    'from_mode' => $previousMode,
                    'to_mode' => $targetMode,
                ]
            );

            $this->broadcastDashboardUpdate($device, $pumpState);
        }

        $commandResult = [
            'action' => 'no_change',
            'applied' => false,
            'wait_seconds' => 0,
        ];

        if ($targetMode === 'manual') {
            $commandResult = $this->switchPumpState(
                $pumpState,
                (bool) $validated['pump_state'],
                'manual_api',
                $validated['reason'] ?? 'manual_command'
            );

            if (($commandResult['action'] ?? '') === 'cooldown_blocked') {
                return response()->json([
                    'message' => 'Perintah ditolak karena cooldown aktif.',
                    'data' => [
                        'mode' => $pumpState->mode,
                        'is_on' => (bool) $pumpState->is_on,
                        'wait_seconds' => $commandResult['wait_seconds'] ?? 0,
                    ],
                ], 429);
            }

            if (($commandResult['action'] ?? '') !== 'no_change') {
                $this->broadcastDashboardUpdate($device, $pumpState);
            }
        }

        return response()->json([
            'message' => 'Perintah pompa diproses.',
            'data' => [
                'device_id' => $device->device_uid,
                'mode' => $pumpState->mode,
                'mode_changed' => $modeChanged,
                'is_on' => (bool) $pumpState->is_on,
                'action' => $commandResult['action'] ?? 'no_change',
                'last_changed_at' => optional($pumpState->last_changed_at)->toISOString(),
            ],
        ]);
    }
}
