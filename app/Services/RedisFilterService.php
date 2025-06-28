<?php
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace App\Services;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use App\Models\Product;

class RedisFilterService
{
    const UNION_KEY_TTL = 60;
    const PRODUCTS_PER_CHUNK = 500;

    protected Connection $redis;

    protected array $localUnionKeyCache = [];

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


        Product::with('parameterValues.parameter')->chunk(self::PRODUCTS_PER_CHUNK,
            function ($products) {
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
     * Get counts for multiple keys in a batch.
     */
    public function getBatchCounts(array $keys): array
    {
        return $this->redis->pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $key) {
                $pipe->scard($key);
            }
        });
    }

    /**
     * Get counts for multiple keys in a batch, taking into account current active filters.
     */
    public function getBatchCountsForActiveFilters(array $filterSets): array
    {
        $keys = [];
        foreach ($filterSets as $filters) {
            $keys [] = $this->buildFilterKeys($filters);
        }

        return $this->redis->pipeline(function ($pipe) use ($filterSets, $keys) {
            foreach ($filterSets as $key => $value) {
                $pipe->sintercard($keys[$key]);
            }
        });

    }

    protected function buildFilterKeys(array $filters): array
    {
        $keys = [];

        foreach ($filters as $paramSlug => $valueSlug) {
            //If a filter has more than one value, build a union key
            if (is_array($valueSlug) && count($valueSlug) > 1) {
                $keys[] = $this->getOrCreateUnionKey($paramSlug, $valueSlug);
            } else {
                //Handle an array with one element
                $valueSlug = is_array($valueSlug) ? $valueSlug[0] : $valueSlug;

                $keys[] = $this->buildKey($paramSlug, $valueSlug);
            }
        }

        return $keys;
    }

    protected function buildKey(string $paramSlug, string $valueSlug): string
    {
        return "$paramSlug:$valueSlug";
    }

    protected function buildUnionKey(string $paramSlug, array $valueSlugs): string
    {
        return "temp:union:$paramSlug:" . implode(',', $valueSlugs);
    }

    protected function getOrCreateUnionKey(string $paramSlug, array $valueSlugs): string
    {
        $key = $this->buildUnionKey($paramSlug, $valueSlugs);

        //Return key quickly from the cache
        if (isset($this->localUnionKeyCache[$key])) {
            return $key;
        }

        //Create a key if not exists
        if (!$this->redis->exists($key)) {
            $keys = [];
            foreach ($valueSlugs as $valueSlug) {
                $keys[] = $this->buildKey($paramSlug, $valueSlug);
            }
            $this->redis->sunionstore($key, ...$keys);

        }

        //Put new key in cache
        $this->localUnionKeyCache[$key] = true;

        //Bump key TTL
        $this->redis->expire($key, self::UNION_KEY_TTL);

        return $key;
    }

}
