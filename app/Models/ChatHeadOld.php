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
        'chat_id',
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
        'user_1_last_seen' => 'datetime',
        'user_2_last_seen' => 'datetime',
        'last_message_time' => 'datetime',
        'user_1_unread_count' => 'integer',
        'user_2_unread_count' => 'integer',
        'last_message_sent_by_user_id' => 'integer',
        'last_message_sender_id' => 'integer',
        'last_message_receiver_id' => 'integer',
        'related_service_id' => 'integer',
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
                $temp_id = $chatHead->user_1_id;
                $temp_name = $chatHead->user_1_name;
                $temp_photo = $chatHead->user_1_photo;
                
                $chatHead->user_1_id = $chatHead->user_2_id;
                $chatHead->user_1_name = $chatHead->user_2_name;
                $chatHead->user_1_photo = $chatHead->user_2_photo;
                
                $chatHead->user_2_id = $temp_id;
                $chatHead->user_2_name = $temp_name;
                $chatHead->user_2_photo = $temp_photo;
            }
            
            // Set defaults
            $chatHead->chat_status = $chatHead->chat_status ?? 'active';
            $chatHead->chat_type = $chatHead->chat_type ?? 'direct';
            $chatHead->user_1_unread_count = $chatHead->user_1_unread_count ?? 0;
            $chatHead->user_2_unread_count = $chatHead->user_2_unread_count ?? 0;
        });
    }

    /**
     * Get or create chat head between two users
     */
    public static function getOrCreateChatHead($user1Id, $user2Id, $serviceId = null, $title = null)
    {
        // Get user details
        $user1 = User::find($user1Id);
        $user2 = User::find($user2Id);
        
        if (!$user1 || !$user2) {
            throw new \Exception('One or both users not found');
        }
        
        // Ensure consistent ordering
        if ($user1Id > $user2Id) {
            $tempId = $user1Id;
            $tempUser = $user1;
            $user1Id = $user2Id;
            $user1 = $user2;
            $user2Id = $tempId;
            $user2 = $tempUser;
        }

        $chatHead = self::where('user_1_id', $user1Id)
            ->where('user_2_id', $user2Id)
            ->first();

        if (!$chatHead) {
            $chatHead = self::create([
                'user_1_id' => $user1Id,
                'user_2_id' => $user2Id,
                'user_1_name' => $user1->name,
                'user_2_name' => $user2->name,
                'user_1_photo' => $user1->avatar ?? '',
                'user_2_photo' => $user2->avatar ?? '',
                'related_service_id' => $serviceId,
                'chat_type' => $serviceId ? 'service' : 'direct',
                'chat_subject' => $title,
                'chat_status' => 'active',
                'user_1_unread_count' => 0,
                'user_2_unread_count' => 0,
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
            'last_message' => $this->getMessagePreview($message),
            'last_message_user_id' => $message->sender_id,
            'last_message_at' => $message->created_at,
        ]);

        // Update unread count for receiver
        if ($message->receiver_id == $this->user1_id) {
            $this->increment('unread_count_user1');
        } else {
            $this->increment('unread_count_user2');
        }
    }

    /**
     * Get message preview for display
     */
    private function getMessagePreview(ChatMessage $message)
    {
        switch ($message->message_type) {
            case 'text':
                return strlen($message->message_content) > 50 ? 
                    substr($message->message_content, 0, 50) . '...' : 
                    $message->message_content;
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
        if ($userId == $this->user1_id) {
            $this->update(['unread_count_user1' => 0]);
        } elseif ($userId == $this->user2_id) {
            $this->update(['unread_count_user2' => 0]);
        }

        // Update message status to read
        ChatMessage::where('chat_id', $this->chat_id)
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get chat partner for a specific user
     */
    public function getChatPartner($userId)
    {
        $partnerId = ($userId == $this->user1_id) ? $this->user2_id : $this->user1_id;
        
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
        if ($userId == $this->user1_id) {
            return $this->unread_count_user1;
        } elseif ($userId == $this->user2_id) {
            return $this->unread_count_user2;
        }
        return 0;
    }

    /**
     * Get user's chats
     */
    public static function getUserChats($userId, $includeArchived = false)
    {
        $query = self::where(function ($query) use ($userId) {
                $query->where('user1_id', $userId)
                      ->orWhere('user2_id', $userId);
            })
            ->where('is_active', true);

        if (!$includeArchived) {
            $query->where(function ($query) use ($userId) {
                $query->where(function ($subQuery) use ($userId) {
                    // For user1: not archived
                    $subQuery->where('user1_id', $userId)
                            ->where('is_archived_user1', false);
                })->orWhere(function ($subQuery) use ($userId) {
                    // For user2: not archived
                    $subQuery->where('user2_id', $userId)
                            ->where('is_archived_user2', false);
                });
            });
        }

        return $query->orderBy('last_message_at', 'desc')->get();
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
     * Check if chat is archived for a user
     */
    public function isArchivedFor($userId)
    {
        if ($userId == $this->user1_id) {
            return $this->is_archived_user1;
        } elseif ($userId == $this->user2_id) {
            return $this->is_archived_user2;
        }
        return false;
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
     * Check if chat is muted for a user
     */
    public function isMutedFor($userId)
    {
        if ($userId == $this->user1_id) {
            return $this->is_muted_user1;
        } elseif ($userId == $this->user2_id) {
            return $this->is_muted_user2;
        }
        return false;
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
