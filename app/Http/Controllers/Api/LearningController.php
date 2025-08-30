<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseUnit;
use App\Models\CourseMaterial;
use App\Models\CourseProgress;
use App\Models\CourseSubscription;
use App\Models\CourseQuiz;
use App\Models\CourseQuizAnswer;
use App\Models\CourseCertificate;
use App\Models\CourseReview;
use App\Models\CourseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LearningController extends Controller
{
    /**
     * Get course learning data for mobile app
     */
    public function getCourseForLearning($courseId)
    {
        try {
            $userId = Auth::id();
            
            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();
            
            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Get course with units and materials
            $course = Course::with([
                'category',
                'units' => function($query) {
                    $query->orderBy('sort_order');
                },
                'units.materials' => function($query) {
                    $query->orderBy('sort_order');
                }
            ])->findOrFail($courseId);

            // Get user's progress for this course
            $progress = CourseProgress::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->get()
                ->keyBy('material_id');

            // Calculate overall progress
            $totalMaterials = 0;
            $completedMaterials = 0;
            $totalTimeSpent = 0;

            foreach ($course->units as $unit) {
                $unit->progress_percentage = 0;
                $unit->completed_materials = 0;
                $unitTimeSpent = 0;

                foreach ($unit->materials as $material) {
                    $totalMaterials++;
                    $materialProgress = $progress->get($material->id);
                    
                    if ($materialProgress) {
                        $material->progress_percentage = $materialProgress->progress_percentage;
                        $material->time_spent_seconds = $materialProgress->time_spent_seconds;
                        $material->completed = $materialProgress->completed;
                        $material->last_accessed_at = $materialProgress->last_accessed_at;
                        
                        if ($materialProgress->completed == 'yes') {
                            $completedMaterials++;
                            $unit->completed_materials++;
                        }
                        
                        $unitTimeSpent += $materialProgress->time_spent_seconds;
                        $totalTimeSpent += $materialProgress->time_spent_seconds;
                    } else {
                        $material->progress_percentage = 0;
                        $material->time_spent_seconds = 0;
                        $material->completed = 'no';
                        $material->last_accessed_at = null;
                    }
                }

                if (count($unit->materials) > 0) {
                    $unit->progress_percentage = round(($unit->completed_materials / count($unit->materials)) * 100, 2);
                }
                $unit->time_spent_seconds = $unitTimeSpent;
            }

            $overallProgress = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0;

            // Get user's certificates for this course
            $certificates = CourseCertificate::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => [
                    'course' => $course,
                    'subscription' => $subscription,
                    'overall_progress' => $overallProgress,
                    'completed_materials' => $completedMaterials,
                    'total_materials' => $totalMaterials,
                    'total_time_spent' => $totalTimeSpent,
                    'certificates' => $certificates,
                    'is_completed' => $overallProgress >= 100
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching course data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update material progress
     */
    public function updateMaterialProgress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_id' => 'required|integer|exists:course_materials,id',
                'progress_percentage' => 'required|numeric|min:0|max:100',
                'time_spent_seconds' => 'required|integer|min:0',
                'completed' => 'required|in:yes,no'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $materialId = $request->material_id;
            $progressPercentage = $request->progress_percentage;
            $timeSpentSeconds = $request->time_spent_seconds;
            $completed = $request->completed;

            // Get material with course info
            $material = CourseMaterial::with('unit.course')->findOrFail($materialId);
            $courseId = $material->unit->course->id;
            $unitId = $material->unit->id;

            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Update or create progress record
            $progress = CourseProgress::updateOrCreate([
                'user_id' => $userId,
                'course_id' => $courseId,
                'unit_id' => $unitId,
                'material_id' => $materialId,
            ], [
                'progress_percentage' => $progressPercentage,
                'time_spent_seconds' => $timeSpentSeconds,
                'completed' => $completed,
                'completed_at' => $completed === 'yes' ? now() : null,
                'last_accessed_at' => now(),
            ]);

            // Check if course is completed and generate certificate
            $this->checkCourseCompletion($userId, $courseId);

            return response()->json([
                'code' => 1,
                'message' => 'Progress updated successfully',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error updating progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Check if course is completed and generate certificate
     */
    private function checkCourseCompletion($userId, $courseId)
    {
        try {
            // Get all materials for this course
            $totalMaterials = CourseMaterial::whereHas('unit', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })->count();

            // Get completed materials for this user
            $completedMaterials = CourseProgress::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('completed', 'yes')
                ->count();

            // If all materials are completed, generate certificate
            if ($totalMaterials > 0 && $completedMaterials >= $totalMaterials) {
                $existingCertificate = CourseCertificate::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->first();

                if (!$existingCertificate) {
                    $course = Course::findOrFail($courseId);
                    $user = Auth::user();
                    
                    CourseCertificate::create([
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                        'issued_date' => now(),
                        'student_name' => $user->name,
                        'course_title' => $course->title,
                        'completion_date' => now()->toDateString(),
                        'instructor_name' => $course->instructor_name,
                        'status' => 'issued'
                    ]);

                    // Create completion notification
                    CourseNotification::create([
                        'user_id' => $userId,
                        'course_id' => $courseId,
                        'type' => 'course_completed',
                        'title' => 'Course Completed!',
                        'message' => "Congratulations! You have completed the course '{$course->title}' and earned a certificate.",
                        'is_read' => 'no'
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the progress update
            Log::error('Error checking course completion: ' . $e->getMessage());
        }
    }

    /**
     * Get user's learning dashboard data
     */
    public function getLearningDashboard()
    {
        try {
            $userId = Auth::id();

            // Get user's active subscriptions
            $subscriptions = CourseSubscription::with('course.category')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->get();

            $dashboardData = [];
            $totalTimeSpent = 0;
            $totalCourses = count($subscriptions);
            $completedCourses = 0;

            foreach ($subscriptions as $subscription) {
                $courseId = $subscription->course_id;
                
                // Get course progress
                $progress = CourseProgress::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->get();

                $totalMaterials = CourseMaterial::whereHas('unit', function($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->count();

                $completedMaterials = $progress->where('completed', 'yes')->count();
                $courseTimeSpent = $progress->sum('time_spent_seconds');
                $totalTimeSpent += $courseTimeSpent;

                $progressPercentage = $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0;
                
                if ($progressPercentage >= 100) {
                    $completedCourses++;
                }

                $dashboardData[] = [
                    'course' => $subscription->course,
                    'subscription' => $subscription,
                    'progress_percentage' => $progressPercentage,
                    'completed_materials' => $completedMaterials,
                    'total_materials' => $totalMaterials,
                    'time_spent_seconds' => $courseTimeSpent,
                    'last_accessed' => $progress->max('last_accessed_at')
                ];
            }

            // Get user's certificates
            $certificates = CourseCertificate::with('course')
                ->where('user_id', $userId)
                ->orderBy('issued_date', 'desc')
                ->get();

            // Get recent notifications
            $notifications = CourseNotification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => [
                    'courses' => $dashboardData,
                    'certificates' => $certificates,
                    'notifications' => $notifications,
                    'stats' => [
                        'total_courses' => $totalCourses,
                        'completed_courses' => $completedCourses,
                        'total_time_spent' => $totalTimeSpent,
                        'completion_rate' => $totalCourses > 0 ? round(($completedCourses / $totalCourses) * 100, 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching dashboard data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's active subscriptions
     */
    public function getMySubscriptions()
    {
        try {
            $userId = Auth::id();
            
            $subscriptions = CourseSubscription::with(['course.category'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $subscriptions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching subscriptions: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's progress across all courses
     */
    public function getMyProgress()
    {
        try {
            $userId = Auth::id();
            
            $progress = CourseProgress::with(['course', 'unit', 'material'])
                ->where('user_id', $userId)
                ->orderBy('last_accessed_at', 'desc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get material content for learning
     */
    public function getMaterialContent($materialId)
    {
        try {
            $userId = Auth::id();
            
            $material = CourseMaterial::with('unit.course')->findOrFail($materialId);
            $courseId = $material->unit->course->id;

            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Get user's progress for this material
            $progress = CourseProgress::where('user_id', $userId)
                ->where('material_id', $materialId)
                ->first();

            $material->progress = $progress;

            // Update last accessed time
            if ($progress) {
                $progress->update(['last_accessed_at' => now()]);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $material
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching material content: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $userId = Auth::id();
            
            $notification = CourseNotification::where('id', $notificationId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $notification->update(['is_read' => 'yes']);

            return response()->json([
                'code' => 1,
                'message' => 'Notification marked as read',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error marking notification as read: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's certificates
     */
    public function getCertificates()
    {
        try {
            $userId = Auth::id();
            
            $certificates = CourseCertificate::with('course')
                ->where('user_id', $userId)
                ->orderBy('issued_date', 'desc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $certificates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching certificates: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Submit course review
     */
    public function submitCourseReview(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|integer|exists:courses,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'required|string|max:1000',
                'title' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }

            $userId = Auth::id();
            $courseId = $request->course_id;

            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Check if user already reviewed this course
            $existingReview = CourseReview::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You have already reviewed this course',
                    'data' => null
                ], 400);
            }

            // Create review
            $review = CourseReview::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'rating' => $request->rating,
                'review' => $request->comment,
                'title' => $request->title ?? 'Course Review',
                'status' => 'approved'
            ]);

            return response()->json([
                'code' => 1,
                'message' => 'Review submitted successfully',
                'data' => $review
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error submitting review: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Track material progress (new centralized method)
     */
    public function trackMaterialProgress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
            /*     'material_id' => 'required|integer|exists:course_materials,id',
                'unit_id' => 'required|integer|exists:course_units,id',
                'time_spent_seconds' => 'required|integer|min:0',
                'progress_percentage' => 'required|numeric|min:0|max:100',
                'is_completed' => 'sometimes|boolean',
                'viewed_at' => 'sometimes|date' */
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $materialId = $request->material_id;
            $unitId = $request->unit_id;
            $timeSpentSeconds = $request->time_spent_seconds;
            $progressPercentage = $request->progress_percentage;
            $isCompleted = $request->is_completed ?? false;

            // Get material with course info
            $material = CourseMaterial::with('unit.course')->findOrFail($materialId);
            $courseId = $material->unit->course->id;

            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Update or create progress record
            $progress = CourseProgress::updateOrCreate([
                'user_id' => $userId,
                'course_id' => $courseId,
                'unit_id' => $unitId,
                'material_id' => $materialId,
            ], [
                'progress_percentage' => $progressPercentage,
                'time_spent_seconds' => $timeSpentSeconds,
                'completed' => $isCompleted,
                'completed_at' => $isCompleted ? now() : null,
                'last_accessed_at' => now(),
            ]);

            return response()->json([
                'code' => 1,
                'message' => 'Progress tracked successfully',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error tracking progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Mark material as completed
     */
    public function markMaterialCompleted(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_id' => 'required|integer|exists:course_materials,id',
                'unit_id' => 'required|integer|exists:course_units,id',
                'total_time_spent_seconds' => 'required|integer|min:0',
                'completed_at' => 'sometimes|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $materialId = $request->material_id;
            $unitId = $request->unit_id;
            $totalTimeSpentSeconds = $request->total_time_spent_seconds;

            // Get material with course info
            $material = CourseMaterial::with('unit.course')->findOrFail($materialId);
            $courseId = $material->unit->course->id;

            // Update progress as completed
            $progress = CourseProgress::updateOrCreate([
                'user_id' => $userId,
                'course_id' => $courseId,
                'unit_id' => $unitId,
                'material_id' => $materialId,
            ], [
                'progress_percentage' => 100.00,
                'time_spent_seconds' => $totalTimeSpentSeconds,
                'completed' => true,
                'completed_at' => now(),
                'last_accessed_at' => now(),
            ]);

            // Check if course is completed and generate certificate
            $this->checkCourseCompletion($userId, $courseId);

            return response()->json([
                'code' => 1,
                'message' => 'Material marked as completed',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error marking material as completed: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get material progress for a user
     */
    public function getMaterialProgress($materialId)
    {
        try {
            $userId = Auth::id();

            // Get the material and check course subscription
            $material = CourseMaterial::with('unit.course')->findOrFail($materialId);
            
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $material->unit->course->id)
                ->where('status', 'active')
                ->first();
                
            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            $progress = CourseProgress::where('user_id', $userId)
                ->where('material_id', $materialId)
                ->first();

            return response()->json([
                'code' => 1,
                'message' => 'Material progress retrieved successfully',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error getting material progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get unit progress for a user
     */
    public function getUnitProgress(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_id' => 'required|integer|exists:course_units,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $unitId = $request->unit_id;

            $progress = CourseProgress::where('user_id', $userId)
                ->where('unit_id', $unitId)
                ->get();

            $totalMaterials = CourseMaterial::where('unit_id', $unitId)->count();
            $completedMaterials = $progress->where('completed', true)->count();
            $totalTimeSpent = $progress->sum('time_spent_seconds');
            $averageProgress = $progress->avg('progress_percentage') ?? 0;

            return response()->json([
                'code' => 1,
                'message' => 'Unit progress retrieved successfully',
                'data' => [
                    'unit_id' => $unitId,
                    'total_materials' => $totalMaterials,
                    'completed_materials' => $completedMaterials,
                    'completion_percentage' => $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0,
                    'total_time_spent_seconds' => $totalTimeSpent,
                    'average_progress_percentage' => round($averageProgress, 2),
                    'materials_progress' => $progress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error getting unit progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get course progress for a user
     */
    public function getCourseProgress($courseId)
    {
        try {
            $userId = Auth::id();

            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            $progress = CourseProgress::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->get();

            $totalMaterials = CourseMaterial::whereHas('unit', function($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })->count();

            $completedMaterials = $progress->where('completed', 'yes')->count();
            $totalTimeSpent = $progress->sum('time_spent_seconds');
            $averageProgress = $progress->avg('progress_percentage') ?? 0;

            return response()->json([
                'code' => 1,
                'message' => 'Course progress retrieved successfully',
                'data' => [
                    'course_id' => $courseId,
                    'total_materials' => $totalMaterials,
                    'completed_materials' => $completedMaterials,
                    'completion_percentage' => $totalMaterials > 0 ? round(($completedMaterials / $totalMaterials) * 100, 2) : 0,
                    'total_time_spent_seconds' => $totalTimeSpent,
                    'average_progress_percentage' => round($averageProgress, 2),
                    'materials_progress' => $progress
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error getting course progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update time tracking for material
     */
    public function updateTimeTracking(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_id' => 'required|integer|exists:course_materials,id',
                'unit_id' => 'required|integer|exists:course_units,id',
                'increment_seconds' => 'required|integer|min:0',
                'updated_at' => 'sometimes|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $materialId = $request->material_id;
            $unitId = $request->unit_id;
            $incrementSeconds = $request->increment_seconds;

            // Get existing progress
            $progress = CourseProgress::where('user_id', $userId)
                ->where('material_id', $materialId)
                ->where('unit_id', $unitId)
                ->first();

            if ($progress) {
                $progress->time_spent_seconds += $incrementSeconds;
                $progress->last_accessed_at = now();
                $progress->save();
            }

            return response()->json([
                'code' => 1,
                'message' => 'Time tracking updated successfully',
                'data' => $progress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error updating time tracking: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Batch update progress for multiple materials
     */
    public function batchUpdateProgress(Request $request)
    {
        try {
            // Support both 'progress_items' and 'updates' formats
            $progressItems = $request->progress_items ?? $request->updates ?? [];
            
            if (empty($progressItems)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Either progress_items or updates field is required',
                    'data' => null
                ], 400);
            }

            $validator = Validator::make(['progress_items' => $progressItems], [
                'progress_items' => 'required|array',
                'progress_items.*.material_id' => 'required|integer|exists:course_materials,id',
                'progress_items.*.time_spent_seconds' => 'nullable|integer|min:0',
                'progress_items.*.progress_percentage' => 'nullable|numeric|min:0|max:100',
                'progress_items.*.completed' => 'nullable|in:yes,no'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }

            $userId = Auth::id();
            $updatedProgress = [];

            DB::beginTransaction();

            foreach ($progressItems as $item) {
                // Get material with course info
                $material = CourseMaterial::with('unit.course')->findOrFail($item['material_id']);
                $courseId = $material->unit->course->id;

                // Check if user is subscribed to this course
                $subscription = CourseSubscription::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->where('status', 'active')
                    ->first();

                if (!$subscription) {
                    continue; // Skip this material if user is not subscribed
                }

                // Update or create progress record
                $progress = CourseProgress::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'material_id' => $item['material_id'],
                        'course_id' => $courseId,
                        'unit_id' => $material->unit_id
                    ],
                    [
                        'progress_percentage' => $item['progress_percentage'] ?? 0,
                        'time_spent_seconds' => $item['time_spent_seconds'] ?? 0,
                        'completed' => $item['completed'] ?? 'no',
                        'last_accessed_at' => now()
                    ]
                );

                $updatedProgress[] = $progress;
            }

            DB::commit();

            return response()->json([
                'code' => 1,
                'message' => 'Progress updated successfully',
                'data' => [
                    'updated_count' => count($updatedProgress),
                    'progress_records' => $updatedProgress
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 0,
                'message' => 'Error updating progress: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Submit quiz answer (complete quiz submission)
     */
    public function submitQuizAnswer(Request $request)
    {
        try {
            // Support both old format (individual fields) and new format (answers array)
            $hasAnswersArray = $request->has('answers') && is_array($request->answers);
            $hasIndividualFields = $request->has('quiz_id') && ($request->has('answer') || $request->has('selected_option'));
            
            if ($hasAnswersArray) {
                $validator = Validator::make($request->all(), [
                    'quiz_id' => 'required|integer|exists:course_quizzes,id',
                    'answers' => 'required|array',
                    'time_taken_seconds' => 'integer|min:0'
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'quiz_id' => 'required|integer|exists:course_quizzes,id',
                    'question_id' => 'nullable|integer',
                    'answer' => 'nullable|string',
                    'selected_option' => 'nullable|string',
                    'time_taken_seconds' => 'integer|min:0'
                ]);
            }

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }

            $userId = Auth::id();

            // Check if user is subscribed to the course that contains this quiz
            $quiz = CourseQuiz::with('unit.course')->find($request->quiz_id);
            if (!$quiz || !$quiz->unit || !$quiz->unit->course) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Quiz not found',
                    'data' => null
                ], 404);
            }

            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $quiz->unit->course->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Prepare answers array
            $answers = [];
            if ($hasAnswersArray) {
                $answers = $request->answers;
            } else {
                // Convert individual fields to answers array format
                $questionKey = 'question_' . ($request->question_id ?? '1');
                $answers = [
                    $questionKey => $request->selected_option ?? $request->answer ?? 'No answer provided'
                ];
            }

            // Calculate score based on answers (implement your scoring logic here)
            $totalQuestions = count($answers);
            $correctAnswers = 0;
            
            // Placeholder scoring logic - you would implement actual answer checking here
            foreach ($answers as $answer) {
                if (is_array($answer) && isset($answer['is_correct']) && $answer['is_correct']) {
                    $correctAnswers++;
                } else {
                    // For backward compatibility, assume 50% are correct for demo
                    $correctAnswers += 0.5;
                }
            }
            
            $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
            $passed = $score >= 70 ? 'yes' : 'no'; // 70% passing grade

            // Get the next attempt number
            $attemptNumber = CourseQuizAnswer::where('user_id', $userId)
                ->where('quiz_id', $request->quiz_id)
                ->max('attempt_number') + 1;

            // Create quiz answer record
            $quizAnswer = CourseQuizAnswer::create([
                'user_id' => $userId,
                'quiz_id' => $request->quiz_id,
                'answers' => $answers,
                'score' => round($score, 2),
                'passed' => $passed,
                'attempt_number' => $attemptNumber,
                'time_taken_seconds' => $request->time_taken_seconds ?? 0,
                'completed_at' => now()
            ]);

            return response()->json([
                'code' => 1,
                'message' => 'Quiz submitted successfully',
                'data' => [
                    'quiz_answer' => $quizAnswer,
                    'score' => $score,
                    'passed' => $passed,
                    'correct_answers' => $correctAnswers,
                    'total_questions' => $totalQuestions
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error submitting quiz: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get quiz answers for a specific quiz
     */
    public function getQuizAnswers($quizId)
    {
        try {
            $userId = Auth::id();

            // Check if user is subscribed to the course that contains this quiz
            $quiz = CourseQuiz::with('unit.course')->find($quizId);
            if (!$quiz || !$quiz->unit || !$quiz->unit->course) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Quiz not found',
                    'data' => null
                ], 404);
            }

            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $quiz->unit->course->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            // Get user's answers for this quiz
            $quizAnswers = CourseQuizAnswer::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Quiz answers retrieved successfully',
                'data' => $quizAnswers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving quiz answers: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get course units for a specific course
     */
    public function getCourseUnits($courseId)
    {
        try {
            $userId = Auth::id();
            
            // Check if user is subscribed to this course
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->first();
                
            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            $units = CourseUnit::where('course_id', $courseId)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Course units retrieved successfully',
                'data' => $units
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving course units: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get unit materials for a specific unit
     */
    public function getUnitMaterials($unitId)
    {
        try {
            $userId = Auth::id();
            
            // Get the unit and check course subscription
            $unit = CourseUnit::with('course')->findOrFail($unitId);
            
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $unit->course_id)
                ->where('status', 'active')
                ->first();
                
            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            $materials = CourseMaterial::where('unit_id', $unitId)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Unit materials retrieved successfully',
                'data' => $materials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving unit materials: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get unit quizzes for a specific unit
     */
    public function getUnitQuizzes($unitId)
    {
        try {
            $userId = Auth::id();
            
            // Get the unit and check course subscription
            $unit = CourseUnit::with('course')->findOrFail($unitId);
            
            $subscription = CourseSubscription::where('user_id', $userId)
                ->where('course_id', $unit->course_id)
                ->where('status', 'active')
                ->first();
                
            if (!$subscription) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You are not subscribed to this course',
                    'data' => null
                ], 403);
            }

            $quizzes = CourseQuiz::where('unit_id', $unitId)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Unit quizzes retrieved successfully',
                'data' => $quizzes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving unit quizzes: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get course reviews for a specific course
     */
    public function getCourseReviews($courseId)
    {
        try {
            $reviews = CourseReview::with('user:id,first_name,last_name,avatar')
                ->where('course_id', $courseId)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Course reviews retrieved successfully',
                'data' => $reviews
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving course reviews: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get learning notifications for the authenticated user
     */
    public function getNotifications()
    {
        try {
            $userId = Auth::id();
            
            $notifications = CourseNotification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'code' => 1,
                'message' => 'Learning notifications retrieved successfully',
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving notifications: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
