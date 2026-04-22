<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardDataUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public array $data) {}

    public function broadcastOn(): array
    {
        return [new Channel('smart-farming.dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return ['data' => $this->data];
    }
}
