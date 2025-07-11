<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceReviewController extends Controller
{
    /**
     * Get reviews for a specific service
     */
    public function index(Request $request, $serviceId)
    {
        try {
            $service = Service::findOrFail($serviceId);
            
            $reviews = ServiceReview::with('reviewer:id,name,avatar')
                ->where('service_id', $serviceId)
                ->active()
                ->newest()
                ->paginate(20);

            return response()->json([
                'code' => 1,
                'message' => 'Reviews fetched successfully',
                'data' => $reviews,
                'service_rating' => [
                    'average_rating' => $service->average_rating,
                    'review_count' => $service->review_count,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to fetch reviews: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Create a new review for a service
     */
    public function store(Request $request, $serviceId)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = Service::findOrFail($serviceId);
            $userId = Auth::id();

            // Check if user is authenticated
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Authentication required',
                    'data' => null
                ], 401);
            }

            // Check if user is trying to review their own service
            if ($service->provider_id == $userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You cannot review your own service',
                    'data' => null
                ], 403);
            }

            // Check if user has already reviewed this service
            $existingReview = ServiceReview::where('service_id', $serviceId)
                ->where('reviewer_id', $userId)
                ->first();

            if ($existingReview) {
                // Update existing review
                $existingReview->update([
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                ]);

                $review = $existingReview->load('reviewer:id,name,avatar');
                
                return response()->json([
                    'code' => 1,
                    'message' => 'Review updated successfully',
                    'data' => $review
                ]);
            } else {
                // Create new review
                $review = ServiceReview::create([
                    'service_id' => $serviceId,
                    'reviewer_id' => $userId,
                    'rating' => $request->rating,
                    'comment' => $request->comment,
                ]);

                $review->load('reviewer:id,name,avatar');

                return response()->json([
                    'code' => 1,
                    'message' => 'Review created successfully',
                    'data' => $review
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to create review: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update a review
     */
    public function update(Request $request, $serviceId, $reviewId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $review = ServiceReview::where('id', $reviewId)
                ->where('service_id', $serviceId)
                ->where('reviewer_id', Auth::id())
                ->firstOrFail();

            $review->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            $review->load('reviewer:id,name,avatar');

            return response()->json([
                'code' => 1,
                'message' => 'Review updated successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to update review: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy($serviceId, $reviewId)
    {
        try {
            $review = ServiceReview::where('id', $reviewId)
                ->where('service_id', $serviceId)
                ->where('reviewer_id', Auth::id())
                ->firstOrFail();

            $review->delete();

            return response()->json([
                'code' => 1,
                'message' => 'Review deleted successfully',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to delete review: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's review for a specific service
     */
    public function getUserReview($serviceId)
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Authentication required',
                    'data' => null
                ], 401);
            }

            $review = ServiceReview::with('reviewer:id,name,avatar')
                ->where('service_id', $serviceId)
                ->where('reviewer_id', $userId)
                ->first();

            return response()->json([
                'code' => 1,
                'message' => 'User review fetched successfully',
                'data' => $review
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to fetch user review: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
