<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Course Model - Represents individual courses in Eight Learning module
 * Contains course details, pricing, instructor info, and metadata
 */
class Course extends Model
{
    use HasFactory;

    protected $table = 'courses';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'detailed_description',
        'instructor_name',
        'instructor_bio',
        'instructor_avatar',
        'cover_image',
        'preview_video',
        'price',
        'currency',
        'duration_hours',
        'difficulty_level',
        'language',
        'requirements',
        'what_you_learn',
        'tags',
        'status',
        'featured',
        'rating_average',
        'rating_count',
        'enrollment_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'enrollment_count' => 'integer',
        'duration_hours' => 'integer',
        'what_you_learn' => 'array',
        'tags' => 'array',
        'requirements' => 'array',
    ];

    /**
     * Get the category that owns the course
     */
    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    /**
     * Get course units
     */
    public function units()
    {
        return $this->hasMany(CourseUnit::class, 'course_id')->orderBy('sort_order');
    }

    /**
     * Get course subscriptions
     */
    public function subscriptions()
    {
        return $this->hasMany(CourseSubscription::class, 'course_id');
    }

    /**
     * Get course reviews
     */
    public function reviews()
    {
        return $this->hasMany(CourseReview::class, 'course_id');
    }

    /**
     * Get course certificates
     */
    public function certificates()
    {
        return $this->hasMany(CourseCertificate::class, 'course_id');
    }

    /**
     * Scope for active courses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured courses
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', 'Yes');
    }

    /**
     * Check if course is free
     */
    public function getIsFreeAttribute()
    {
        return $this->price == 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        if ($this->price == 0) {
            return 'Free';
        }
        return $this->currency . ' ' . number_format($this->price, 2);
    }
}