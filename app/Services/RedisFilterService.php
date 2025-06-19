<?php

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use function Laravel\Prompts\info;

class RedisFilterService
{
    protected Connection $redis;

    public function __construct()
    {
        $this->redis = Redis::connection('filters');
    }

    /**
     * Delete all filters from Redis.
     */
    public function clearAll(): void
    {
        $this->redis->flushdb();
    }

    /**
     * Rebuild filters in Redis.
     */
    public function rebuild(): void
    {
        $this->clearAll();


        Product::with('parameterValues.parameter')->chunk(500, function ($products) {
            $this->redis->pipeline(function ($pipe) use ($products) {
                foreach ($products as $product) {
                    foreach ($product->parameterValues as $value) {
                        $key = $this->buildKey(
                            $value->parameter->slug,
                            $value->slug
                        );

                        $pipe->sadd($key, $product->id);
                    }
                }
            });
        });

    }

    /**
     * Get a list of products that match the specified filters.
     *
     */
    public function getMatchingProductIds(array $filters): array
    {
        $keys = $this->buildFilterKeys($filters);

        return $keys ? $this->redis->sinter($keys) : [];
    }

    /**
     * Get the number of products for a specific filter value, taking into account the current filters.
     *
     */
    public function getCountForValue(array $activeFilters, string $paramSlug, string $valueSlug): int
    {
        $keys = $this->buildFilterKeys($activeFilters);
        $keys[] = $this->buildKey($paramSlug, $valueSlug);

        return count($this->redis->sinter($keys));
    }

    protected function buildKey(string $paramSlug, string $valueSlug): string
    {
        return "{$paramSlug}:{$valueSlug}";
    }

    protected function buildFilterKeys(array $filters): array
    {
        $keys = [];

        foreach ($filters as $paramSlug => $valueSlugs) {
            foreach ((array)$valueSlugs as $valueSlug) {
                $keys[] = $this->buildKey($paramSlug, $valueSlug);
            }
        }

        return $keys;
    }
}
