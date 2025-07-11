<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceBookmark extends Model
{
    use HasFactory;

    protected $table = 'service_bookmarks_2';

    protected $fillable = [
        'user_id',
        'service_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who bookmarked the service
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookmarked service
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Check if a user has bookmarked a specific service
     */
    public static function isBookmarked($userId, $serviceId)
    {
        return self::where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->exists();
    }

    /**
     * Toggle bookmark for a service
     */
    public static function toggle($userId, $serviceId)
    {
        $bookmark = self::where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return false; // Removed
        } else {
            self::create([
                'user_id' => $userId,
                'service_id' => $serviceId,
            ]);
            return true; // Added
        }
    }

    /**
     * Get user's bookmarked services
     */
    public static function getUserBookmarks($userId, $limit = 20)
    {
        return self::with('service')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($limit);
    }
}
