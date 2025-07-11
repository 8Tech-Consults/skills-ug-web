<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServiceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'reviewer_id',
        'rating',
        'comment',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update service average rating when a review is created
        static::created(function ($review) {
            $review->updateServiceRating();
        });

        // Update service average rating when a review is updated
        static::updated(function ($review) {
            $review->updateServiceRating();
        });

        // Update service average rating when a review is deleted
        static::deleted(function ($review) {
            $review->updateServiceRating();
        });
    }

    /**
     * Update the service's average rating and review count
     */
    public function updateServiceRating()
    {
        $serviceId = $this->service_id;
        
        // Calculate average rating and count using SQL to avoid deadlocks
        $stats = DB::selectOne("
            SELECT 
                COALESCE(AVG(rating), 0) as avg_rating,
                COUNT(*) as review_count
            FROM service_reviews 
            WHERE service_id = ? AND status = 'Active'
        ", [$serviceId]);

        // Update the service table directly with SQL
        DB::update("
            UPDATE services 
            SET average_rating = ?, review_count = ? 
            WHERE id = ?
        ", [
            round($stats->avg_rating, 2),
            $stats->review_count,
            $serviceId
        ]);
    }

    /**
     * Get the service that this review belongs to
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user who wrote this review
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope a query to only include active reviews
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope a query to order by newest first
     */
    public function scopeNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
