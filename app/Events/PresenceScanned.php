<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class PresenceScanned implements ShouldBroadcast
{
    use Dispatchable;

    // ponytail: one queue job per scan via ShouldBroadcast; switch to ShouldBroadcastNow when sync delivery beats durability.

    public function __construct(
        public int $sessionId,
        public array $student,
        public array $analytics,
    ) {}

    /** @return list<Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("presence.session.{$this->sessionId}")];
    }

    public function broadcastAs(): string
    {
        return 'presence.scanned';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['session_id' => $this->sessionId, 'student' => $this->student, 'analytics' => $this->analytics];
    }
}
