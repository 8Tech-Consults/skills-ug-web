<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceBookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceBookmarkController extends Controller
{
    /**
     * Toggle bookmark for a service
     */
    public function toggle(Request $request, $serviceId)
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

            // Check if service exists
            $service = Service::findOrFail($serviceId);

            // Toggle bookmark
            $isBookmarked = ServiceBookmark::toggle($userId, $serviceId);

            return response()->json([
                'code' => 1,
                'message' => $isBookmarked ? 'Service bookmarked successfully' : 'Bookmark removed successfully',
                'data' => [
                    'is_bookmarked' => $isBookmarked,
                    'service_id' => $serviceId,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to toggle bookmark: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Check if user has bookmarked a service
     */
    public function check($serviceId)
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

            $isBookmarked = ServiceBookmark::isBookmarked($userId, $serviceId);

            return response()->json([
                'code' => 1,
                'message' => 'Bookmark status retrieved',
                'data' => [
                    'is_bookmarked' => $isBookmarked,
                    'service_id' => $serviceId,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to check bookmark status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's bookmarked services
     */
    public function index(Request $request)
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

            $bookmarks = ServiceBookmark::getUserBookmarks($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Bookmarks retrieved successfully',
                'data' => $bookmarks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve bookmarks: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove all bookmarks for a user
     */
    public function clear()
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

            ServiceBookmark::where('user_id', $userId)->delete();

            return response()->json([
                'code' => 1,
                'message' => 'All bookmarks cleared successfully',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to clear bookmarks: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
