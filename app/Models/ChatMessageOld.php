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
        'sender_id',
        'receiver_id',
        'message_type',
        'message_content',
        'media_url',
        'media_type',
        'media_size',
        'thumbnail_url',
        'is_read',
        'read_at',
        'is_delivered',
        'delivered_at',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'reply_to_message_id',
        'reactions',
        'metadata',
        'is_system_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_read' => 'boolean',
        'is_delivered' => 'boolean',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'is_system_message' => 'boolean',
        'media_size' => 'integer',
        'reactions' => 'array',
        'metadata' => 'array',
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
        });

        static::created(function ($message) {
            // Update chat head with latest message
            $chatHead = ChatHead::where('chat_id', $message->chat_id)->first();
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
        $messageData = [
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_type' => $type,
            'message_content' => $content,
            'is_delivered' => true,
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
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
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
            'message_content' => $newContent,
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
        $reactions = $this->reactions ?: [];
        
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

        $this->update(['reactions' => $reactions]);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction($userId)
    {
        $reactions = $this->reactions ?: [];
        
        $reactions = array_filter($reactions, function ($reaction) use ($userId) {
            return $reaction['user_id'] !== $userId;
        });

        $this->update(['reactions' => array_values($reactions)]);
    }

    /**
     * Get reply preview for quoted messages
     */
    public function getReplyPreview()
    {
        if (!$this->reply_to_message_id) {
            return null;
        }

        $originalMessage = self::where('message_id', $this->reply_to_message_id)->first();
        
        if (!$originalMessage) {
            return null;
        }

        $preview = '';
        switch ($originalMessage->message_type) {
            case 'text':
                $preview = strlen($originalMessage->message_content) > 30 ? 
                    substr($originalMessage->message_content, 0, 30) . '...' : 
                    $originalMessage->message_content;
                break;
            case 'image':
                $preview = '📷 Photo';
                break;
            case 'video':
                $preview = '🎥 Video';
                break;
            case 'voice':
                $preview = '🎵 Voice message';
                break;
            case 'file':
                $preview = '📄 File';
                break;
            default:
                $preview = 'Message';
        }

        return [
            'message_id' => $originalMessage->message_id,
            'sender_id' => $originalMessage->sender_id,
            'content' => $preview,
            'type' => $originalMessage->message_type,
        ];
    }

    /**
     * Check if message can be edited
     */
    public function canBeEdited($userId)
    {
        // Only sender can edit, and only text messages within 24 hours
        return $this->sender_id == $userId && 
               $this->message_type == 'text' && 
               !$this->is_deleted &&
               $this->created_at->diffInHours(now()) < 24;
    }

    /**
     * Check if message can be deleted
     */
    public function canBeDeleted($userId)
    {
        // Only sender can delete, within reasonable time frame
        return $this->sender_id == $userId && 
               !$this->is_deleted &&
               $this->created_at->diffInHours(now()) < 48;
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
            'type' => $this->message_type,
            'content' => $this->message_content,
            'is_read' => $this->is_read,
            'is_delivered' => $this->is_delivered,
            'is_edited' => $this->is_edited,
            'reactions' => $this->reactions ?: [],
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'edited_at' => $this->edited_at?->toISOString(),
        ];

        // Add media data if present
        if ($this->media_url) {
            $data['media_url'] = $this->media_url;
            $data['media_type'] = $this->media_type;
            $data['media_size'] = $this->media_size;
            $data['thumbnail_url'] = $this->thumbnail_url;
        }

        // Add reply data if present
        if ($this->reply_to_message_id) {
            $data['reply_to'] = $this->getReplyPreview();
        }

        return $data;
    }

    /**
     * Get chat head this message belongs to
     */
    public function chatHead()
    {
        return $this->belongsTo(ChatHead::class, 'chat_id', 'chat_id');
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
            ->where('message_type', 'text')
            ->where('message_content', 'LIKE', '%' . $searchTerm . '%')
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
