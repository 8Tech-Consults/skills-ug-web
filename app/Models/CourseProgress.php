<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseProgress Model - Tracks detailed user progress through courses
 * Implements the 5-minute minimum viewing rule and comprehensive progress tracking
 */
class CourseProgress extends Model
{
    use HasFactory;

    protected $table = 'course_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'unit_id',
        'material_id',
        'progress_percentage',
        'time_spent_seconds',
        'completed',
        'completed_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'progress_percentage' => 'decimal:2',
        'time_spent_seconds' => 'integer',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    // 5-minute minimum viewing rule
    const MINIMUM_VIEW_TIME_SECONDS = 300;

    /**
     * Get the user that owns the progress
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the progress
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the unit that owns the progress
     */
    public function unit()
    {
        return $this->belongsTo(CourseUnit::class, 'unit_id');
    }

    /**
     * Get the material that owns the progress
     */
    public function material()
    {
        return $this->belongsTo(CourseMaterial::class, 'material_id');
    }

    /**
     * Check if material is completed
     */
    public function getIsCompletedAttribute()
    {
        return $this->completed === 'Yes';
    }

    /**
     * Check if meets minimum viewing time (5 minutes)
     */
    public function getMeetsMinimumViewingTimeAttribute()
    {
        return $this->time_spent_seconds >= self::MINIMUM_VIEW_TIME_SECONDS;
    }

    /**
     * Get formatted time spent
     */
    public function getFormattedTimeSpentAttribute()
    {
        if ($this->time_spent_seconds < 60) {
            return $this->time_spent_seconds . 's';
        } elseif ($this->time_spent_seconds < 3600) {
            $minutes = intval($this->time_spent_seconds / 60);
            $seconds = $this->time_spent_seconds % 60;
            if ($seconds == 0) {
                return $minutes . 'm';
            }
            return $minutes . 'm ' . $seconds . 's';
        } else {
            $hours = intval($this->time_spent_seconds / 3600);
            $minutes = intval(($this->time_spent_seconds % 3600) / 60);
            if ($minutes == 0) {
                return $hours . 'h';
            }
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Scope for completed progress
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', 'Yes');
    }

    /**
     * Track material viewing progress
     */
    public static function trackProgress($userId, $courseId, $unitId, $materialId, $timeSpent, $progressPercentage = null)
    {
        $progress = static::firstOrCreate([
            'user_id' => $userId,
            'course_id' => $courseId,
            'unit_id' => $unitId,
            'material_id' => $materialId,
        ], [
            'progress_percentage' => 0,
            'time_spent_seconds' => 0,
            'completed' => 'No',
        ]);

        // Update time spent
        $progress->time_spent_seconds += $timeSpent;

        // Update progress percentage if provided
        if ($progressPercentage !== null) {
            $progress->progress_percentage = $progressPercentage;
        }

        // Check if material should be marked as completed (5-minute rule)
        if ($progress->time_spent_seconds >= self::MINIMUM_VIEW_TIME_SECONDS && $progress->completed !== 'Yes') {
            $progress->completed = 'Yes';
            $progress->completed_at = now();
        }

        // Update access time
        $progress->last_accessed_at = now();
        $progress->save();

        return $progress;
    }

    /**
     * Get user's course progress summary
     */
    public static function getCourseProgressSummary($userId, $courseId)
    {
        $progressRecords = static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->get();

        if ($progressRecords->isEmpty()) {
            return [
                'total_materials' => 0,
                'completed_materials' => 0,
                'progress_percentage' => 0,
                'total_time_spent' => 0,
                'last_accessed' => null,
            ];
        }

        $totalMaterials = $progressRecords->count();
        $completedMaterials = $progressRecords->where('completed', 'Yes')->count();
        $progressPercentage = $totalMaterials > 0 ? ($completedMaterials / $totalMaterials) * 100 : 0;
        $totalTimeSpent = $progressRecords->sum('time_spent_seconds');
        $lastAccessed = $progressRecords->max('last_accessed_at');

        return [
            'total_materials' => $totalMaterials,
            'completed_materials' => $completedMaterials,
            'progress_percentage' => round($progressPercentage, 2),
            'total_time_spent' => $totalTimeSpent,
            'last_accessed' => $lastAccessed,
        ];
    }

    /**
     * Get user's learning streak
     */
    public static function getUserLearningStreak($userId)
    {
        $progressRecords = static::where('user_id', $userId)
            ->whereNotNull('last_accessed_at')
            ->orderBy('last_accessed_at', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->last_accessed_at->format('Y-m-d');
            });

        if ($progressRecords->isEmpty()) {
            return 0;
        }

        $dates = $progressRecords->keys()->sort()->reverse()->values();
        $streak = 0;
        $currentDate = now()->format('Y-m-d');

        foreach ($dates as $date) {
            if ($date === $currentDate || 
                now()->parse($currentDate)->diffInDays(now()->parse($date)) === $streak + 1) {
                $streak++;
                $currentDate = $date;
            } else {
                break;
            }
        }

        return $streak;
    }
}