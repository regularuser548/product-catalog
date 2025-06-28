<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\FilterUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Parameter;
use App\Services\RedisFilterService;
use Illuminate\Support\Facades\Cache;

class FilterController extends Controller
{
    use FilterUtils;

    private RedisFilterService $filterService;

    public function __construct(RedisFilterService $service)
    {
        $this->filterService = $service;
    }

    /**
     * Get available filters with counts based on active filters.
     *
     */
    public function index(Request $request): JsonResponse
    {
        $activeFilters = $request->input('filter');

        //Sort filters
        if (is_array($activeFilters)) {
            $this->sortFilters($activeFilters);
        }

        $parameters = Cache::remember('all_parameters', now()->addMinutes(30), function () {
            return Parameter::with('parameterValues')->get();
        });

        $response = [];


        foreach ($parameters as $parameter) {
            $paramSlug = $parameter->slug;

            $filterData = [
                'name' => $parameter->name,
                'slug' => $paramSlug,
                'values' => []
            ];

            $counts = $this->calculateCounts($activeFilters, $parameter->slug,
                $parameter->parameterValues->pluck('slug')->toArray());

            foreach ($parameter->parameterValues as $key => $value) {
                $valueSlug = $value->slug;
                $count = $counts[$key];
                $isActive = is_array($activeFilters) && $this->isFilterActive($valueSlug, $activeFilters);

                $filterData['values'][] = [
                    'value' => $value->value,
                    'slug' => $valueSlug,
                    'count' => $count,
                    'active' => $isActive,
                ];
            }

            // Sort the values: active first, then by quantity
            usort($filterData['values'], function ($a, $b) {
                if ($a['active'] && !$b['active']) return -1;
                if (!$a['active'] && $b['active']) return 1;
                return $b['count'] <=> $a['count'];
            });

            $response[] = $filterData;
        }

        return response()->json($response);

    }

    /**
     * Calculate count:
     * - If no active filters: total count for each value
     * - If active filters exist: count of products that match current active filters for each value
     *
     */
    private function calculateCounts(?array $activeFilters, string $parameter_slug, array $parameterValues): array
    {
        $keys = [];

        // If there are no active filters - return the total count for each value
        if (!$activeFilters) {

            foreach ($parameterValues as $value) {
                $keys[] = "$parameter_slug:$value";
            }

            return $this->filterService->getBatchCounts($keys);
        }

        // If active filters present - return count of products that match current active filters for each value
        foreach ($parameterValues as $value) {
            $keys[] = array_merge($activeFilters, [$parameter_slug => $value]);
        }

        return $this->filterService->getBatchCountsForActiveFilters($keys);
    }

}
