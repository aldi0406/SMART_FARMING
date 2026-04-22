<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Concerns\HandlesPumpState;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use HandlesPumpState;

    public function index()
    {
        return view('welcome', [
            'refreshIntervalSeconds' => (int) config('smart_farming.dashboard.refresh_interval_seconds', 1),
            'reverb' => [
                'key' => (string) config('broadcasting.connections.reverb.key'),
                'host' => (string) config('broadcasting.connections.reverb.options.host', '127.0.0.1'),
                'port' => (int) config('broadcasting.connections.reverb.options.port', 8080),
                'scheme' => (string) config('broadcasting.connections.reverb.options.scheme', 'http'),
                'channel' => 'smart-farming.dashboard',
                'event' => 'dashboard.updated',
            ],
        ]);
    }

    public function live(Request $request): JsonResponse
    {
        $device = $this->resolveDashboardDevice($request);

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat aktif tidak ditemukan.',
            ], 404);
        }

        $pumpState = $this->ensurePumpState($device);

        return response()->json([
            'message' => 'Data realtime berhasil diambil.',
            'data' => $this->getDashboardPayload($device, $pumpState),
        ]);
    }

    public function mode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:manual,automatic'],
        ]);

        $device = $this->resolveDashboardDevice($request);

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat aktif tidak ditemukan.',
            ], 404);
        }

        $pumpState = $this->ensurePumpState($device);

        if ($pumpState->mode === $validated['mode']) {
            return response()->json([
                'message' => 'Mode pompa tidak berubah.',
                'data' => [
                    'mode' => $pumpState->mode,
                    'is_on' => (bool) $pumpState->is_on,
                ],
            ]);
        }

        $fromMode = $pumpState->mode;

        $pumpState->forceFill([
            'mode' => $validated['mode'],
            'last_command_source' => 'web_dashboard',
            'last_reason' => 'mode_changed_by_dashboard',
        ])->save();

        $this->logPumpAction(
            $pumpState,
            'web_dashboard',
            'mode_changed',
            (bool) $pumpState->is_on,
            (bool) $pumpState->is_on,
            'mode_changed_by_dashboard',
            [
                'from_mode' => $fromMode,
                'to_mode' => $validated['mode'],
            ]
        );

        $this->broadcastDashboardUpdate($device, $pumpState);

        return response()->json([
            'message' => 'Mode pompa berhasil diubah.',
            'data' => [
                'mode' => $pumpState->mode,
                'is_on' => (bool) $pumpState->is_on,
            ],
        ]);
    }

    public function siram(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:on,off,toggle'],
        ]);

        $device = $this->resolveDashboardDevice($request);

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat aktif tidak ditemukan.',
            ], 404);
        }

        $pumpState = $this->ensurePumpState($device);

        if ($pumpState->mode !== 'manual') {
            return response()->json([
                'message' => 'Kontrol siram hanya tersedia saat mode manual.',
                'data' => [
                    'mode' => $pumpState->mode,
                    'is_on' => (bool) $pumpState->is_on,
                ],
            ], 409);
        }

        $desiredState = match ($validated['action']) {
            'on' => true,
            'off' => false,
            default => ! (bool) $pumpState->is_on,
        };

        $result = $this->switchPumpState(
            $pumpState,
            $desiredState,
            'web_dashboard',
            'manual_siram_button'
        );

        if (($result['action'] ?? '') === 'cooldown_blocked') {
            return response()->json([
                'message' => 'Perintah ditolak karena cooldown aktif.',
                'data' => [
                    'mode' => $pumpState->mode,
                    'is_on' => (bool) $pumpState->is_on,
                    'wait_seconds' => $result['wait_seconds'] ?? 0,
                ],
            ], 429);
        }

        if (($result['action'] ?? '') !== 'no_change') {
            $this->broadcastDashboardUpdate($device, $pumpState);
        }

        return response()->json([
            'message' => 'Perintah siram berhasil diproses.',
            'data' => [
                'mode' => $pumpState->mode,
                'is_on' => (bool) $pumpState->is_on,
                'action' => $result['action'] ?? 'no_change',
                'last_changed_at' => optional($pumpState->last_changed_at)->toISOString(),
            ],
        ]);
    }

    private function resolveDashboardDevice(Request $request): ?Device
    {
        $requestedUid = $request->query('device_uid');
        $defaultUid = (string) config('smart_farming.dashboard.default_device_uid', 'ESP32-001');
        $targetUid = $requestedUid ?: $defaultUid;

        $byUid = Device::query()
            ->where('device_uid', $targetUid)
            ->where('is_active', true)
            ->first();

        if ($byUid) {
            return $byUid;
        }

        return Device::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }
}
