<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public array $payload = []
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('ops')];
    }

    public function broadcastAs(): string
    {
        return 'ops.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'ts' => now()->getTimestampMs(),
            ...$this->payload,
        ];
    }
}
