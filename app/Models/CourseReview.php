<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseReview Model - Represents user reviews and ratings for courses
 * Manages course feedback, ratings, and review moderation
 */
class CourseReview extends Model
{
    use HasFactory;

    protected $table = 'course_reviews';

    protected $fillable = [
        'user_id',
        'course_id',
        'rating',
        'review_text',
        'helpful_count',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
    ];

    /**
     * Get the user that owns the review
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the review
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending reviews
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if review is approved
     */
    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if review is pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Get star rating display
     */
    public function getStarRatingAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get truncated review text
     */
    public function getTruncatedReviewTextAttribute($maxLength = 150)
    {
        if (strlen($this->review_text) <= $maxLength) {
            return $this->review_text;
        }
        return substr($this->review_text, 0, $maxLength) . '...';
    }

    /**
     * Get helpful count text
     */
    public function getHelpfulCountTextAttribute()
    {
        if ($this->helpful_count == 0) {
            return 'No helpful votes';
        } elseif ($this->helpful_count == 1) {
            return '1 person found this helpful';
        } else {
            return $this->helpful_count . ' people found this helpful';
        }
    }

    /**
     * Get course rating statistics
     */
    public static function getCourseRatingStats($courseId)
    {
        $reviews = static::where('course_id', $courseId)
            ->where('status', 'approved')
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'rating_distribution' => [
                    '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0
                ],
            ];
        }

        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating');
        
        $ratingDistribution = [
            '5' => $reviews->where('rating', 5)->count(),
            '4' => $reviews->where('rating', 4)->count(),
            '3' => $reviews->where('rating', 3)->count(),
            '2' => $reviews->where('rating', 2)->count(),
            '1' => $reviews->where('rating', 1)->count(),
        ];

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * Check if user has reviewed a course
     */
    public static function hasUserReviewedCourse($userId, $courseId)
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * Get user's review for a course
     */
    public static function getUserCourseReview($userId, $courseId)
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
    }
}