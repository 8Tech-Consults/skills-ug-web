<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Get all services with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Service::query()->with(['jobCategory', 'provider']);

            // Apply filters
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('tags', 'LIKE', "%{$search}%");
                });
            }

            if ($request->has('category_id') && $request->category_id) {
                $query->where('job_category_id', $request->category_id);
            }

            if ($request->has('location') && $request->location) {
                $query->where('location', 'LIKE', "%{$request->location}%");
            }

            if ($request->has('min_price') && $request->min_price) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->has('max_price') && $request->max_price) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('rating') && $request->rating) {
                $query->where('average_rating', '>=', $request->rating);
            }

            if ($request->has('is_featured') && $request->is_featured == '1') {
                $query->where('promotional_badge', '!=', null);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'latest');
            switch ($sortBy) {
                case 'popular':
                    $query->orderBy('review_count', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('average_rating', 'desc');
                    break;
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'latest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Apply status filter (only active services)
            $query->where('status', 'active');

            // Pagination
            $perPage = min($request->get('limit', 15), 50); // Max 50 per page
            $services = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Services retrieved successfully',
                'data' => [
                    'services' => $services->items(),
                    'pagination' => [
                        'current_page' => $services->currentPage(),
                        'last_page' => $services->lastPage(),
                        'per_page' => $services->perPage(),
                        'total' => $services->total(),
                        'from' => $services->firstItem(),
                        'to' => $services->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single service by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $service = Service::with(['jobCategory', 'provider'])->find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found',
                    'error' => 'The requested service does not exist'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service retrieved successfully',
                'data' => $service
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = JobCategory::withCount('services')
                ->where('status', 1)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Service categories retrieved successfully',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured services
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $limit = min($request->get('limit', 10), 20);
            
            $services = Service::with(['jobCategory', 'provider'])
                ->where('status', 'active')
                ->where('promotional_badge', '!=', null)
                ->orderBy('average_rating', 'desc')
                ->orderBy('review_count', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Featured services retrieved successfully',
                'data' => $services
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured services',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search services by query
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->get('q');
            $limit = min($request->get('limit', 15), 50);

            $services = Service::with(['jobCategory', 'provider'])
                ->where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('tags', 'LIKE', "%{$query}%")
                      ->orWhere('provider_name', 'LIKE', "%{$query}%");
                })
                ->orderBy('average_rating', 'desc')
                ->orderBy('review_count', 'desc')
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Services search completed successfully',
                'data' => [
                    'services' => $services->items(),
                    'pagination' => [
                        'current_page' => $services->currentPage(),
                        'last_page' => $services->lastPage(),
                        'per_page' => $services->perPage(),
                        'total' => $services->total(),
                        'from' => $services->firstItem(),
                        'to' => $services->lastItem(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search services',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
