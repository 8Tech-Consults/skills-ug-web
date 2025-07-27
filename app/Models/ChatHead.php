<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChatHead extends Model
{
    use HasFactory;

    protected $table = 'chat_heads_2';

    protected $fillable = [
        'user_1_id',
        'user_2_id',
        'user_1_name',
        'user_2_name',
        'user_1_photo',
        'user_2_photo',
        'user_1_last_seen',
        'user_2_last_seen',
        'last_message_sent_by_user_id',
        'last_message_body',
        'last_message_time',
        'last_message_status',
        'last_message_type',
        'last_message_sender_id',
        'last_message_receiver_id',
        'user_1_unread_count',
        'user_2_unread_count',
        'chat_status',
        'chat_type',
        'related_service_id',
        'chat_subject',
        'user_1_notification_preference',
        'user_2_notification_preference',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_message_time' => 'datetime',
        'user_1_last_seen' => 'datetime',
        'user_2_last_seen' => 'datetime',
        'user_1_unread_count' => 'integer',
        'user_2_unread_count' => 'integer',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chatHead) {
            // Generate unique chat_id if not provided
            if (!$chatHead->chat_id) {
                $chatHead->chat_id = 'chat_' . Str::uuid();
            }
            
            // Ensure user_1_id is always smaller than user_2_id for consistency
            if ($chatHead->user_1_id > $chatHead->user_2_id) {
                $temp = $chatHead->user_1_id;
                $chatHead->user_1_id = $chatHead->user_2_id;
                $chatHead->user_2_id = $temp;
            }
        });
    }

    /**
     * Get or create chat head between two users
     */
    public static function getOrCreateChatHead($user1Id, $user2Id, $serviceId = null, $title = null)
    {
        // Ensure consistent ordering
        if ($user1Id > $user2Id) {
            $temp = $user1Id;
            $user1Id = $user2Id;
            $user2Id = $temp;
        }

        $chatHead = self::where('user_1_id', $user1Id)
            ->where('user_2_id', $user2Id)
            ->first();

        if (!$chatHead) {
            $chatHead = self::create([
                'user_1_id' => $user1Id,
                'user_2_id' => $user2Id,
                'related_service_id' => $serviceId,
                'chat_type' => $serviceId ? 'service' : 'direct',
                'chat_subject' => $title,
            ]);
        }

        return $chatHead;
    }

    /**
     * Update chat head with latest message
     */
    public function updateWithLatestMessage(ChatMessage $message)
    {
        $this->update([
            'last_message_body' => $this->getMessagePreview($message),
            'last_message_sender_id' => $message->sender_id,
            'last_message_time' => $message->created_at,
        ]);

        // Update unread count for receiver
        if ($message->receiver_id == $this->user_1_id) {
            $this->increment('user_1_unread_count');
        } else {
            $this->increment('user_2_unread_count');
        }
    }

    /**
     * Get message preview for display
     */
    private function getMessagePreview(ChatMessage $message)
    {
        switch ($message->type) {
            case 'text':
                return strlen($message->body) > 50 ? 
                    substr($message->body, 0, 50) . '...' : 
                    $message->body;
            case 'image':
                return '📷 Photo';
            case 'video':
                return '🎥 Video';
            case 'voice':
                return '🎵 Voice';
            case 'file':
                return '📄 File';
            default:
                return 'Message';
        }
    }

    /**
     * Mark messages as read for a user
     */
    public function markAsRead($userId)
    {
        if ($userId == $this->user_1_id) {
            $this->update(['user_1_unread_count' => 0]);
        } elseif ($userId == $this->user_2_id) {
            $this->update(['user_2_unread_count' => 0]);
        }

        // Update message status to read
        ChatMessage::where('chat_head_id', $this->id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    /**
     * Get chat partner for a specific user
     */
    public function getChatPartner($userId)
    {
        $partnerId = ($userId == $this->user_1_id) ? $this->user_2_id : $this->user_1_id;
        
        // Get partner details from User model
        $partner = User::find($partnerId);
        
        return $partner ? [
            'id' => $partner->id,
            'name' => $partner->name,
            'email' => $partner->email,
            'avatar' => $partner->avatar ?? '',
        ] : null;
    }

    /**
     * Get unread count for a specific user
     */
    public function getUnreadCount($userId)
    {
        if ($userId == $this->user_1_id) {
            return $this->user_1_unread_count;
        } elseif ($userId == $this->user_2_id) {
            return $this->user_2_unread_count;
        }
        return 0;
    }

    /**
     * Get user's chats
     */
    public static function getUserChats($userId, $includeArchived = false)
    {
        $query = self::where(function ($query) use ($userId) {
                $query->where('user_1_id', $userId)
                      ->orWhere('user_2_id', $userId);
            })
            ->where('chat_status', 'active');

        return $query->orderBy('last_message_time', 'desc')->get();
    }

    /**
     * Archive/unarchive chat for a user
     */
    public function toggleArchive($userId)
    {
        if ($userId == $this->user1_id) {
            $this->update(['is_archived_user1' => !$this->is_archived_user1]);
        } elseif ($userId == $this->user2_id) {
            $this->update(['is_archived_user2' => !$this->is_archived_user2]);
        }
    }

    /**
     * Mute/unmute chat for a user
     */
    public function toggleMute($userId)
    {
        if ($userId == $this->user1_id) {
            $this->update(['is_muted_user1' => !$this->is_muted_user1]);
        } elseif ($userId == $this->user2_id) {
            $this->update(['is_muted_user2' => !$this->is_muted_user2]);
        }
    }

    /**
     * Get related service if exists
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Get messages for this chat
     */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id', 'chat_id');
    }

    /**
     * Get latest messages
     */
    public function latestMessages($limit = 50)
    {
        return $this->messages()
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Get users participating in this chat
     */
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }
}
