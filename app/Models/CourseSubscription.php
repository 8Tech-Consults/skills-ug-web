<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CourseSubscription Model - Tracks user subscriptions to courses
 * Manages subscription status, payment info, and access control
 */
class CourseSubscription extends Model
{
    use HasFactory;

    protected $table = 'course_subscriptions';

    protected $fillable = [
        'user_id',
        'course_id',
        'subscription_type',
        'status',
        'subscribed_at',
        'expires_at',
        'payment_status',
        'payment_amount',
        'payment_currency',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the subscription
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get payment receipts for this subscription
     */
    public function receipts()
    {
        return $this->hasMany(PaymentReceipt::class, 'subscription_id');
    }

    /**
     * Check if subscription is currently active
     */
    public function getIsActiveAttribute()
    {
        if ($this->status !== 'active' || $this->payment_status !== 'paid') {
            return false;
        }
        
        if ($this->expires_at) {
            return Carbon::now()->isBefore($this->expires_at);
        }
        
        return true;
    }

    /**
     * Get days remaining until expiration
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->expires_at) {
            return -1; // No expiration
        }
        
        $now = Carbon::now();
        if ($now->isAfter($this->expires_at)) {
            return 0; // Expired
        }
        
        return $now->diffInDays($this->expires_at);
    }

    /**
     * Check if subscription is trial
     */
    public function getIsTrialAttribute()
    {
        return $this->subscription_type === 'trial';
    }

    /**
     * Check if subscription is free
     */
    public function getIsFreeAttribute()
    {
        return $this->payment_amount == 0;
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('payment_status', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    /**
     * Scope for expiring subscriptions
     */
    public function scopeExpiring($query, $withinDays = 7)
    {
        return $query->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                Carbon::now(),
                Carbon::now()->addDays($withinDays)
            ]);
    }

    /**
     * Get formatted payment amount
     */
    public function getFormattedPaymentAmountAttribute()
    {
        if ($this->payment_amount == 0) {
            return 'Free';
        }
        return $this->payment_currency . ' ' . number_format($this->payment_amount, 2);
    }

    /**
     * Check if user has active subscription to course
     */
    public static function hasActiveSubscription($userId, $courseId)
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->active()
            ->exists();
    }
}