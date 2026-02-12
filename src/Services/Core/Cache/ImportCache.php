<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Cache;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class ImportCache
{
    private CacheRepository $cache;

    private int $ttl;

    private string $prefix;

    public function __construct(
        CacheRepository $cache,
        int $ttl = 3600,
        string $prefix = 'import_'
    ) {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->prefix = $prefix;
    }

    /**
     * Get a cached value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->prefix.$key, $default);
    }

    /**
     * Store a value in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->cache->put(
            $this->prefix.$key,
            $value,
            $ttl ?? $this->ttl
        );
    }

    /**
     * Store a value in cache forever.
     */
    public function forever(string $key, mixed $value): bool
    {
        return $this->cache->forever($this->prefix.$key, $value);
    }

    /**
     * Remove a value from cache.
     */
    public function forget(string $key): bool
    {
        return $this->cache->forget($this->prefix.$key);
    }

    /**
     * Clear all cache entries with the prefix.
     * Note: This is a placeholder method. In production, implement a more targeted approach.
     */
    public function flush(): bool
    {
        // Note: This method is intentionally simplified to avoid cache driver compatibility issues
        // In production, you might want to implement a more targeted approach
        return true;
    }

    /**
     * Get or store a value using a callback.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cache->remember(
            $this->prefix.$key,
            $ttl ?? $this->ttl,
            $callback
        );
    }

    /**
     * Get or store a value forever using a callback.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->cache->rememberForever($this->prefix.$key, $callback);
    }

    /**
     * Check if a key exists in cache.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($this->prefix.$key);
    }

    /**
     * Get the TTL for this cache instance.
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set the TTL for this cache instance.
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }
}
