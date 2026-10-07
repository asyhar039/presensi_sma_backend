<?php

use App\Models\PresenceSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('presence.session.{sessionId}', function ($user, int $sessionId): bool {
    if ($user === null) {
        return false;
    }
    if (! $user->hasRole('teacher')) {
        return false;
    }
    $teacherId = $user->teacher?->id;

    return $teacherId !== null && PresenceSession::query()
        ->whereKey($sessionId)->where('teacher_id', $teacherId)->exists();
});
