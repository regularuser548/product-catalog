<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Parameter;
use App\Services\RedisFilterService;

class FilterController extends Controller
{
    private RedisFilterService $filterService;

    public function __construct(RedisFilterService $service)
    {
        $this->filterService = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $activeFilters = $request->input('filter');

        $parameters = Parameter::with('parameterValues')->get();

        $response = [];

        foreach ($parameters as $parameter) {
            $paramSlug = $parameter->slug;

            $response[$paramSlug] = $parameter->parameterValues->map(function ($value) use ($activeFilters, $paramSlug) {
                return [
                    'slug' => $value->slug,
                    'value' => $value->value,
                    'count' => $this->filterService->getCountForValue($activeFilters, $paramSlug, $value->slug),
                    'active' => in_array($paramSlug, $activeFilters[$paramSlug] ?? [])
                ];
            });
        }

        return response()->json(compact('response'));
    }
}
