<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Short-TTL cache for dashboard aggregates, keyed by member and day.
 * Actions that write tasks or projects call bust() so every member's
 * counts stay honest within seconds of a change.
 */
final readonly class DashboardCache
{
    private const int TTL_SECONDS = 60;

    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function remember(string $name, callable $callback): mixed
    {
        return Cache::remember($this->key($name), self::TTL_SECONDS, fn (): mixed => $callback());
    }

    /**
     * Invalidate every member's dashboard cache for the organization.
     */
    public static function bust(int $organizationId): void
    {
        Cache::increment("dashboard:{$organizationId}:version");
    }

    public static function keyFor(int $organizationId, int $userId, string $name): string
    {
        $bucket = now()->format('Y-m-d');
        $version = (int) Cache::get("dashboard:{$organizationId}:version", 0);

        return "dashboard:{$organizationId}:{$userId}:{$version}:{$bucket}:{$name}";
    }

    private function key(string $name): string
    {
        return self::keyFor($this->organizationId, $this->userId, $name);
    }
}
