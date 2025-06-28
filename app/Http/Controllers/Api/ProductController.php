<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RedisFilterService;
use App\Traits\FilterUtils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use FilterUtils;

    private array $sortFields = [
        'id',
        'price',
    ];

    private RedisFilterService $filterService;

    public function __construct(RedisFilterService $service)
    {
        $this->filterService = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $sort = $request->input('sort_by');
        $limit = $request->integer('limit', 10);
        $filters = $request->input('filter');

        $query = Product::query();

        if (is_array($filters)) {
            $this->sortFilters($filters);
            $query->whereIn('id', $this->filterService->getMatchingProductIds($filters));
        }

        if ($sort) {
            [$field, $direction] = explode('_', $sort);
            if (in_array($field, $this->sortFields) && in_array($direction, ['asc', 'desc']))
                $query->orderBy($field, $direction);
        }

        return response()->json($query->paginate($limit));
    }
}
