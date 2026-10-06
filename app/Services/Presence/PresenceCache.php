<?php

namespace App\Services\Presence;

use Illuminate\Support\Facades\Cache;

final class PresenceCache
{
    public const ROTATE_SECONDS = 300;

    // ponytail: cache-aside while DB stays source of truth; upgrade to Redis pub/sub + websocket when live push needed.

    public static function sessionKey(int $sessionId): string
    {
        return "presence:session:{$sessionId}";
    }

    public static function qrKey(string $key): string
    {
        return "presence:qr:{$key}";
    }

    public static function rosterKey(int $sessionId): string
    {
        return "presence:roster:{$sessionId}";
    }

    public static function countersKey(int $sessionId): string
    {
        return "presence:counters:{$sessionId}";
    }

    public static function counters(int $sessionId): array
    {
        return Cache::get(self::countersKey($sessionId), []);
    }

    public static function increment(int $sessionId, string $field): void
    {
        $counters = self::counters($sessionId);
        $counters[$field] = ((int) ($counters[$field] ?? 0)) + 1;
        Cache::put(self::countersKey($sessionId), $counters, 3600);
    }

    public static function forgetSession(int $sessionId, ?string $qrKey = null): void
    {
        Cache::forget(self::sessionKey($sessionId));
        Cache::forget(self::rosterKey($sessionId));
        Cache::forget(self::countersKey($sessionId));
        if ($qrKey !== null) {
            Cache::forget(self::qrKey($qrKey));
        }
    }
}
