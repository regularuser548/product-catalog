<?php

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;
use function Laravel\Prompts\info;

class RedisFilterService
{
    const UNION_KEY_TTL = 60;
    const PRODUCTS_PER_CHUNK = 500;

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


        Product::with('parameterValues.parameter')->chunk(self::PRODUCTS_PER_CHUNK, function ($products) {
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
     * Get the number of products for a specific filter value.
     *
     */
    public function getCountForFilterValue(string $paramSlug, string $valueSlug): int
    {
        $key = $this->buildKey($paramSlug, $valueSlug);

        return $this->redis->scard($key);
    }

    /**
     * Get count of products that match the specified filters.
     */
    public function getCountForMatchingFilters(array $filters): int
    {
        $keys = $this->buildFilterKeys($filters);

        return $keys ? $this->redis->sintercard($keys) : 0;
    }


    protected function getOrCreateUnionKey(string $paramSlug, array $valueSlugs): string
    {
        $key = $this->buildUnionKey($paramSlug, $valueSlugs);

        if (!$this->redis->exists($key)) {
            $keys = [];
            foreach ($valueSlugs as $valueSlug) {
                $keys[] = $this->buildKey($paramSlug, $valueSlug);
            }
            $this->redis->sunionstore($key, ...$keys);
        }

        //Bump TTL
        $this->redis->expire($key, self::UNION_KEY_TTL);

        return $key;
    }

    protected function buildKey(string $paramSlug, string $valueSlug): string
    {
        return "{$paramSlug}:{$valueSlug}";
    }

    protected function buildUnionKey(string $paramSlug, array $valueSlugs): string
    {
        //Dedupe and sort
        $valueSlugs = array_unique($valueSlugs);
        sort($valueSlugs);

        return "temp:union:{$paramSlug}:" . implode(',', $valueSlugs);
    }

    protected function buildFilterKeys(array $filters): array
    {
        $keys = [];

        foreach ($filters as $paramSlug => $valueSlug) {
            if (is_array($valueSlug) && count($valueSlug) > 1) {
                $keys[] = $this->getOrCreateUnionKey($paramSlug, $valueSlug);
            } else {
                //Handle array with one element
                $valueSlug = is_array($valueSlug) ? $valueSlug[0] : $valueSlug;

                $keys[] = $this->buildKey($paramSlug, $valueSlug);
            }
        }

        return $keys;
    }
}
