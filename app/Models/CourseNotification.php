<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseNotification Model - Handles course-related notifications
 * Manages different types of notifications for the Eight Learning module
 */
class CourseNotification extends Model
{
    use HasFactory;

    protected $table = 'course_notifications';

    protected $fillable = [
        'user_id',
        'course_id',
        'type',
        'title',
        'message',
        'read_status',
        'action_url',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the notification
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Check if notification is read
     */
    public function getIsReadAttribute()
    {
        return $this->read_status === 'read';
    }

    /**
     * Check if notification is unread
     */
    public function getIsUnreadAttribute()
    {
        return $this->read_status === 'unread';
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read_status', 'unread');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('read_status', 'read');
    }

    /**
     * Get notification type icon
     */
    public function getTypeIconAttribute()
    {
        switch (strtolower($this->type)) {
            case 'new_course':
                return '🆕';
            case 'course_update':
                return '🔄';
            case 'reminder':
                return '⏰';
            case 'certificate':
                return '🏆';
            case 'quiz_result':
                return '📊';
            case 'subscription_expiry':
                return '⚠️';
            case 'achievement':
                return '🎉';
            case 'payment_success':
                return '💳';
            case 'payment_failed':
                return '❌';
            default:
                return '📢';
        }
    }

    /**
     * Get notification priority
     */
    public function getPriorityAttribute()
    {
        switch (strtolower($this->type)) {
            case 'subscription_expiry':
            case 'payment_failed':
                return 1; // High priority
            case 'certificate':
            case 'quiz_result':
            case 'payment_success':
                return 2; // Medium priority
            case 'reminder':
            case 'course_update':
                return 3; // Normal priority
            case 'new_course':
            case 'achievement':
                return 4; // Low priority
            default:
                return 5; // Lowest priority
        }
    }

    /**
     * Get type display text
     */
    public function getTypeDisplayTextAttribute()
    {
        switch (strtolower($this->type)) {
            case 'new_course':
                return 'New Course';
            case 'course_update':
                return 'Course Update';
            case 'reminder':
                return 'Learning Reminder';
            case 'certificate':
                return 'Certificate';
            case 'quiz_result':
                return 'Quiz Result';
            case 'subscription_expiry':
                return 'Subscription';
            case 'achievement':
                return 'Achievement';
            case 'payment_success':
                return 'Payment';
            case 'payment_failed':
                return 'Payment Failed';
            default:
                return 'Notification';
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->read_status = 'read';
        $this->save();
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->read_status = 'unread';
        $this->save();
    }

    /**
     * Get unread count for user
     */
    public static function getUnreadCount($userId)
    {
        return static::where('user_id', $userId)
            ->where('read_status', 'unread')
            ->count();
    }

    /**
     * Mark all notifications as read for user
     */
    public static function markAllAsReadForUser($userId)
    {
        return static::where('user_id', $userId)
            ->where('read_status', 'unread')
            ->update(['read_status' => 'read']);
    }

    /**
     * Create course completion notification
     */
    public static function createCourseCompletionNotification($userId, $courseId, $courseTitle)
    {
        return static::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'type' => 'certificate',
            'title' => 'Course Completed! 🎉',
            'message' => "Congratulations! You have successfully completed \"$courseTitle\". Your certificate is ready for download.",
            'read_status' => 'unread',
            'action_url' => '/eight-learning/certificates',
        ]);
    }

    /**
     * Create subscription expiry notification
     */
    public static function createSubscriptionExpiryNotification($userId, $courseId, $courseTitle, $daysRemaining)
    {
        if ($daysRemaining == 0) {
            $message = "Your subscription to \"$courseTitle\" has expired. Renew now to continue learning.";
        } elseif ($daysRemaining == 1) {
            $message = "Your subscription to \"$courseTitle\" expires tomorrow. Renew now to avoid interruption.";
        } else {
            $message = "Your subscription to \"$courseTitle\" expires in $daysRemaining days. Renew now to continue learning.";
        }

        return static::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'type' => 'subscription_expiry',
            'title' => 'Subscription Expiring Soon ⚠️',
            'message' => $message,
            'read_status' => 'unread',
            'action_url' => "/eight-learning/course/$courseId",
        ]);
    }
}