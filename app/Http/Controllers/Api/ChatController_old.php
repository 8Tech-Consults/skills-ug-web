<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatHead;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get user's chat heads
     */
    public function getChats(Request $request)
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Authentication required',
                    'data' => null
                ], 401);
            }

            $status = $request->get('status', 'active');
            $chats = ChatHead::getUserChats($userId, $status);

            // Format chats for response
            $formattedChats = $chats->map(function ($chat) use ($userId) {
                $partner = $chat->getChatPartner($userId);
                
                return [
                    'id' => $chat->id,
                    'partner' => $partner,
                    'last_message' => [
                        'body' => $chat->last_message_body,
                        'time' => $chat->last_message_time,
                        'type' => $chat->last_message_type,
                        'status' => $chat->last_message_status,
                        'sender_id' => $chat->last_message_sender_id,
                    ],
                    'unread_count' => $chat->getUnreadCount($userId),
                    'chat_status' => $chat->chat_status,
                    'chat_type' => $chat->chat_type,
                    'chat_subject' => $chat->chat_subject,
                    'related_service_id' => $chat->related_service_id,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                ];
            });

            return response()->json([
                'code' => 1,
                'message' => 'Chats retrieved successfully',
                'data' => $formattedChats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve chats: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get or create chat head
     */
    public function getOrCreateChat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'partner_id' => 'required|integer|exists:users,id',
                'service_id' => 'nullable|integer',
                'subject' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $partnerId = $request->partner_id;
            $serviceId = $request->service_id;
            $subject = $request->subject;

            if ($userId == $partnerId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Cannot start chat with yourself',
                    'data' => null
                ], 400);
            }

            $chatHead = ChatHead::getOrCreateChatHead($userId, $partnerId, $serviceId, $subject);
            $partner = $chatHead->getChatPartner($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Chat retrieved successfully',
                'data' => [
                    'chat_head_id' => $chatHead->id,
                    'partner' => $partner,
                    'chat_type' => $chatHead->chat_type,
                    'chat_subject' => $chatHead->chat_subject,
                    'related_service_id' => $chatHead->related_service_id,
                    'unread_count' => $chatHead->getUnreadCount($userId),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to get or create chat: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get messages for a chat
     */
    public function getMessages(Request $request, $chatHeadId)
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Authentication required',
                    'data' => null
                ], 401);
            }

            // Verify user is part of this chat
            $chatHead = ChatHead::find($chatHeadId);
            if (!$chatHead || ($chatHead->user_1_id != $userId && $chatHead->user_2_id != $userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $lastMessageId = $request->get('last_message_id');
            $limit = min($request->get('limit', 50), 100); // Max 100 messages

            $messages = ChatMessage::getChatMessages($chatHeadId, $lastMessageId, $limit);

            // Mark messages as read
            ChatMessage::markAllAsRead($chatHeadId, $userId);
            $chatHead->markAsRead($userId);

            // Update last seen
            $chatHead->updateLastSeen($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Messages retrieved successfully',
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to retrieve messages: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, $chatHeadId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:text,image,video,audio,document,address,gps_location',
                'body' => 'required_if:type,text,address|string',
                'image_url' => 'required_if:type,image|string',
                'video_url' => 'required_if:type,video|string',
                'audio_url' => 'required_if:type,audio|string',
                'document_url' => 'required_if:type,document|string',
                'document_name' => 'nullable|string',
                'document_size' => 'nullable|string',
                'gps_latitude' => 'required_if:type,gps_location|string',
                'gps_longitude' => 'required_if:type,gps_location|string',
                'reply_to_message_id' => 'nullable|integer|exists:chat_messages_2,id',
                'message_priority' => 'nullable|in:normal,high,urgent',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            
            // Verify user is part of this chat
            $chatHead = ChatHead::find($chatHeadId);
            if (!$chatHead || ($chatHead->user_1_id != $userId && $chatHead->user_2_id != $userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            // Determine receiver
            $receiverId = $chatHead->user_1_id == $userId ? $chatHead->user_2_id : $chatHead->user_1_id;

            // Prepare message data
            $messageData = $request->only([
                'type', 'body', 'image_url', 'video_url', 'audio_url', 
                'document_url', 'document_name', 'document_size',
                'address', 'gps_latitude', 'gps_longitude', 
                'reply_to_message_id', 'message_priority'
            ]);

            // Add GPS location data if provided
            if ($request->type === 'gps_location') {
                $messageData['gps_location'] = [
                    'latitude' => $request->gps_latitude,
                    'longitude' => $request->gps_longitude,
                    'address' => $request->get('address'),
                ];
            }

            // Get reply preview if replying to a message
            if ($request->reply_to_message_id) {
                $repliedMessage = ChatMessage::find($request->reply_to_message_id);
                if ($repliedMessage) {
                    $messageData['reply_preview'] = $this->getReplyPreview($repliedMessage);
                }
            }

            $message = ChatMessage::createMessage($chatHeadId, $userId, $receiverId, $messageData);

            return response()->json([
                'code' => 1,
                'message' => 'Message sent successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to send message: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update message status (delivered/read)
     */
    public function updateMessageStatus(Request $request, $messageId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:delivered,read',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $message = ChatMessage::find($messageId);

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            // Only receiver can update message status
            if ($message->receiver_id != $userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Not authorized to update this message',
                    'data' => null
                ], 403);
            }

            if ($request->status === 'delivered') {
                $message->markAsDelivered();
            } elseif ($request->status === 'read') {
                $message->markAsRead();
            }

            return response()->json([
                'code' => 1,
                'message' => 'Message status updated successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to update message status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Edit a message
     */
    public function editMessage(Request $request, $messageId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'body' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $message = ChatMessage::find($messageId);

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            // Only sender can edit message
            if ($message->sender_id != $userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Not authorized to edit this message',
                    'data' => null
                ], 403);
            }

            $success = $message->editMessage($request->body);

            if (!$success) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message cannot be edited',
                    'data' => null
                ], 400);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Message edited successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to edit message: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage($messageId)
    {
        try {
            $userId = Auth::id();
            $message = ChatMessage::find($messageId);

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            // Only sender can delete message
            if ($message->sender_id != $userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Not authorized to delete this message',
                    'data' => null
                ], 403);
            }

            $message->deleteMessage();

            return response()->json([
                'code' => 1,
                'message' => 'Message deleted successfully',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to delete message: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Add reaction to message
     */
    public function addReaction(Request $request, $messageId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'emoji' => 'required|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userId = Auth::id();
            $message = ChatMessage::find($messageId);

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            $message->addReaction($userId, $request->emoji);

            return response()->json([
                'code' => 1,
                'message' => 'Reaction added successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to add reaction: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction($messageId)
    {
        try {
            $userId = Auth::id();
            $message = ChatMessage::find($messageId);

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            $message->removeReaction($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Reaction removed successfully',
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to remove reaction: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Archive/unarchive chat
     */
    public function toggleArchive($chatHeadId)
    {
        try {
            $userId = Auth::id();
            $chatHead = ChatHead::find($chatHeadId);

            if (!$chatHead || ($chatHead->user_1_id != $userId && $chatHead->user_2_id != $userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleArchive();

            return response()->json([
                'code' => 1,
                'message' => 'Chat archive status updated',
                'data' => ['chat_status' => $chatHead->chat_status]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to toggle archive: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Toggle mute for chat
     */
    public function toggleMute($chatHeadId)
    {
        try {
            $userId = Auth::id();
            $chatHead = ChatHead::find($chatHeadId);

            if (!$chatHead || ($chatHead->user_1_id != $userId && $chatHead->user_2_id != $userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleMute($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Chat mute status updated',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Failed to toggle mute: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get reply preview for a message
     */
    private function getReplyPreview($message)
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
            case 'audio':
                return '🎵 Audio';
            case 'document':
                return '📄 ' . ($message->document_name ?: 'Document');
            case 'address':
                return '📍 ' . $message->address;
            case 'gps_location':
                return '🗺️ Location';
            default:
                return 'Message';
        }
    }
}
