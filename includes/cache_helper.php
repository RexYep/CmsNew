<?php
// ============================================
// includes/cache_helper.php
// Upstash Redis via REST API — walang extension!
// ============================================

class Cache {

    // ---- Core REST caller ----
    private static function call(array $cmd): mixed {
        if (!CACHE_ENABLED) return null;

        $ch = curl_init(rtrim(REDIS_URL, '/') . '/pipeline');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . REDIS_TOKEN,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode([$cmd]),
            CURLOPT_TIMEOUT        => 2,   // 2s max — huwag mapabagal ang page
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) return null;
        $decoded = json_decode($res, true);
        return $decoded[0]['result'] ?? null;
    }

    // ---- GET ----
    public static function get(string $key): mixed {
        $raw = self::call(['GET', $key]);
        if ($raw === null) return null;
        $decoded = json_decode($raw, true);
        return ($decoded !== null) ? $decoded : $raw;
    }

    // ---- SET with TTL ----
    public static function set(string $key, mixed $val, int $ttl = 300): bool {
        $cmd = $ttl > 0
            ? ['SET', $key, json_encode($val), 'EX', $ttl]
            : ['SET', $key, json_encode($val)];
        return self::call($cmd) === 'OK';
    }

    // ---- DELETE one key ----
    public static function del(string $key): void {
        self::call(['DEL', $key]);
    }

    // ---- DELETE keys by pattern (e.g. "dashboard:*") ----
    public static function delPattern(string $pattern): void {
        if (!CACHE_ENABLED) return;
        $cursor = '0';
        $keys   = [];
        do {
            $r      = self::call(['SCAN', $cursor, 'MATCH', $pattern, 'COUNT', '100']);
            $cursor = is_array($r) ? ($r[0] ?? '0') : '0';
            $found  = is_array($r) ? ($r[1] ?? []) : [];
            $keys   = array_merge($keys, $found);
        } while ($cursor !== '0');

        foreach (array_chunk($keys, 10) as $chunk) {
            self::call(array_merge(['DEL'], $chunk));
        }
    }

    // ---- REMEMBER: get-or-set helper ----
    // Pinaka-convenient na gamitin sa PHP pages:
    //   $data = Cache::remember('my_key', 300, fn() => $conn->query(...)... );
    public static function remember(string $key, int $ttl, callable $cb): mixed {
        $cached = self::get($key);
        if ($cached !== null) return $cached;   // HIT — walang DB query!

        $val = $cb();
        if ($val !== null) self::set($key, $val, $ttl);
        return $val;
    }

    // ---- PING — buhay ba ang Redis? ----
    public static function ping(): bool {
        return self::call(['PING']) === 'PONG';
    }
}