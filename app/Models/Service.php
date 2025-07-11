<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'job_category_id',
        'provider_id',
        'status',
        'tags',
        'description',
        'details',
        'price',
        'price_description',
        'delivery_time',
        'delivery_time_description',
        'client_requirements',
        'process_description',
        'cover_image',
        'gallery',
        'intro_video_url',
        'provider_name',
        'provider_logo',
        'location',
        'languages_spoken',
        'experience_years',
        'certifications',
        'refund_policy',
        'promotional_badge',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'review_count' => 'integer',
    ];

    /**
     * Get the job category that owns the service
     */
    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * Get the provider (user) that owns the service
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the reviews for the service
     */
    public function reviews()
    {
        return $this->hasMany(ServiceReview::class);
    }

    /**
     * Get active reviews for the service
     */
    public function activeReviews()
    {
        return $this->hasMany(ServiceReview::class)->active()->newest();
    }

    /**
     * Check if a user has already reviewed this service
     */
    public function hasBeenReviewedBy($userId)
    {
        return $this->reviews()->where('reviewer_id', $userId)->exists();
    }

    /**
     * Get the user's review for this service
     */
    public function getUserReview($userId)
    {
        return $this->reviews()->where('reviewer_id', $userId)->first();
    }

    /**
     * Scope a query to only include active services
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to order by highest rated first
     */
    public function scopeHighestRated($query)
    {
        return $query->orderBy('average_rating', 'desc');
    }
}
