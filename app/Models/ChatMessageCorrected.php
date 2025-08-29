<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages_2';

    protected $fillable = [
        'message_id',
        'chat_id',
        'chat_head_id',
        'sender_id',
        'receiver_id',
        'user_1_id',
        'user_2_id',
        'type',
        'body',
        'status',
        'audio_url',
        'video_url',
        'image_url',
        'document_url',
        'document_name',
        'document_size',
        'address',
        'gps_latitude',
        'gps_longitude',
        'gps_location',
        'reply_to_message_id',
        'reply_preview',
        'delivered_at',
        'read_at',
        'message_priority',
        'metadata',
        'encryption_key',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'reactions',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'chat_head_id' => 'integer',
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'user_1_id' => 'integer',
        'user_2_id' => 'integer',
        'reply_to_message_id' => 'integer',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($message) {
            // Generate unique message_id if not provided
            if (!$message->message_id) {
                $message->message_id = 'msg_' . Str::uuid();
            }
            
            // Set defaults
            $message->status = $message->status ?? 'sent';
            $message->type = $message->type ?? 'text';
            $message->message_priority = $message->message_priority ?? 'normal';
            $message->is_edited = $message->is_edited ?? false;
            $message->is_deleted = $message->is_deleted ?? false;
        });

        static::created(function ($message) {
            // Update chat head with latest message
            $chatHead = ChatHead::where('chat_id', $message->chat_id)->first();
            if (!$chatHead && $message->chat_head_id) {
                // Try to find by chat_head_id if chat_id didn't work
                $chatHead = ChatHead::find($message->chat_head_id);
            }
            
            if ($chatHead) {
                $chatHead->updateWithLatestMessage($message);
            }
        });
    }

    /**
     * Create a new message
     */
    public static function createMessage($chatId, $senderId, $receiverId, $content, $type = 'text', $mediaData = [])
    {
        // Find chat head by chat_id or create it
        $chatHead = ChatHead::where('chat_id', $chatId)->first();
        
        if (!$chatHead) {
            // If no chat_id match, try to find by users
            $chatHead = ChatHead::where(function($query) use ($senderId, $receiverId) {
                $query->where('user_1_id', $senderId)->where('user_2_id', $receiverId);
            })->orWhere(function($query) use ($senderId, $receiverId) {
                $query->where('user_1_id', $receiverId)->where('user_2_id', $senderId);
            })->first();
        }
        
        if (!$chatHead) {
            $chatHead = ChatHead::getOrCreateChatHead($senderId, $receiverId);
        }

        $messageData = [
            'chat_id' => $chatHead->chat_id,
            'chat_head_id' => $chatHead->id,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'user_1_id' => $chatHead->user_1_id,
            'user_2_id' => $chatHead->user_2_id,
            'type' => $type,
            'body' => $content,
            'status' => 'sent',
            'delivered_at' => now(),
        ];

        // Add media data if provided
        if (!empty($mediaData)) {
            $messageData = array_merge($messageData, $mediaData);
        }

        return self::create($messageData);
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Edit message content
     */
    public function editContent($newContent)
    {
        $this->update([
            'body' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Soft delete message
     */
    public function softDelete()
    {
        $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    /**
     * Add reaction to message
     */
    public function addReaction($userId, $emoji)
    {
        $reactions = $this->reactions ? json_decode($this->reactions, true) : [];
        
        // Remove existing reaction from this user
        $reactions = array_filter($reactions, function ($reaction) use ($userId) {
            return $reaction['user_id'] !== $userId;
        });

        // Add new reaction
        $reactions[] = [
            'user_id' => $userId,
            'emoji' => $emoji,
            'created_at' => now()->toISOString(),
        ];

        $this->update(['reactions' => json_encode($reactions)]);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction($userId)
    {
        $reactions = $this->reactions ? json_decode($this->reactions, true) : [];
        
        $reactions = array_filter($reactions, function ($reaction) use ($userId) {
            return $reaction['user_id'] !== $userId;
        });

        $this->update(['reactions' => json_encode(array_values($reactions))]);
    }

    /**
     * Get formatted message for display
     */
    public function getFormattedMessage()
    {
        if ($this->is_deleted) {
            return [
                'message_id' => $this->message_id,
                'content' => 'This message was deleted',
                'type' => 'deleted',
                'is_deleted' => true,
            ];
        }

        $data = [
            'message_id' => $this->message_id,
            'chat_id' => $this->chat_id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'type' => $this->type,
            'content' => $this->body,
            'is_read' => $this->status === 'read',
            'is_delivered' => $this->status !== 'pending',
            'is_edited' => $this->is_edited,
            'reactions' => $this->reactions ? json_decode($this->reactions, true) : [],
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),
        ];

        // Add media URLs based on type
        switch ($this->type) {
            case 'image':
                $data['media_url'] = $this->image_url;
                break;
            case 'video':
                $data['media_url'] = $this->video_url;
                break;
            case 'audio':
            case 'voice':
                $data['media_url'] = $this->audio_url;
                break;
            case 'document':
            case 'file':
                $data['media_url'] = $this->document_url;
                $data['document_name'] = $this->document_name;
                $data['document_size'] = $this->document_size;
                break;
        }

        // Add reply data if present
        if ($this->reply_to_message_id) {
            $data['reply_to'] = [
                'message_id' => $this->reply_to_message_id,
                'preview' => $this->reply_preview,
            ];
        }

        return $data;
    }

    /**
     * Get chat head this message belongs to
     */
    public function chatHead()
    {
        return $this->belongsTo(ChatHead::class, 'chat_head_id');
    }

    /**
     * Get sender user
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get receiver user
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get messages for a specific chat
     */
    public static function getChatMessages($chatId, $limit = 50, $beforeMessageId = null)
    {
        $query = self::where('chat_id', $chatId)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        if ($beforeMessageId) {
            $beforeMessage = self::where('message_id', $beforeMessageId)->first();
            if ($beforeMessage) {
                $query->where('created_at', '<', $beforeMessage->created_at);
            }
        }

        return $query->limit($limit)->get()->reverse()->values();
    }

    /**
     * Search messages in a chat
     */
    public static function searchInChat($chatId, $searchTerm)
    {
        return self::where('chat_id', $chatId)
            ->where('type', 'text')
            ->where('body', 'LIKE', '%' . $searchTerm . '%')
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Accessors for backward compatibility
    public function getMessageContentAttribute()
    {
        return $this->body;
    }
    
    public function getMessageTypeAttribute()
    {
        return $this->type;
    }
    
    public function getIsReadAttribute()
    {
        return $this->status === 'read';
    }
    
    public function getIsDeliveredAttribute()
    {
        return $this->status !== 'pending';
    }
}
