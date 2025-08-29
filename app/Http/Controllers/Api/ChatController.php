<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatHead;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

            $includeArchived = $request->boolean('include_archived', false);
            $chats = ChatHead::getUserChats($userId, $includeArchived);

            // Format chats for response
            $formattedChats = $chats->map(function ($chat) use ($userId) {
                $partner = $chat->getChatPartner($userId);
                
                return [
                    'chat_id' => $chat->chat_id,
                    'id' => $chat->id,
                    'partner' => $partner,
                    'service' => $chat->service ? [
                        'id' => $chat->service->id,
                        'title' => $chat->service->title,
                        'image' => $chat->service->image_url,
                    ] : null,
                    'last_message' => [
                        'content' => $chat->last_message,
                        'time' => $chat->last_message_at?->toISOString(),
                        'sender_id' => $chat->last_message_user_id,
                    ],
                    'unread_count' => $chat->getUnreadCount($userId),
                    'is_archived' => $chat->isArchivedFor($userId),
                    'is_muted' => $chat->isMutedFor($userId),
                    'chat_type' => $chat->chat_type,
                    'title' => $chat->title,
                    'created_at' => $chat->created_at->toISOString(),
                    'updated_at' => $chat->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'code' => 1,
                'message' => 'Chats retrieved successfully',
                'data' => [
                    'chats' => $formattedChats,
                    'total' => $formattedChats->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving chats: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get or create chat head between users
     */
    public function getOrCreateChat(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'partner_id' => 'required|integer|exists:clients,id',
                'service_id' => 'nullable|integer|exists:services,id',
                'title' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $partnerId = $request->partner_id;
            $serviceId = $request->service_id;
            $title = $request->title;

            if ($userId == $partnerId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Cannot create chat with yourself',
                    'data' => null
                ], 400);
            }

            $chatHead = ChatHead::getOrCreateChatHead($userId, $partnerId, $serviceId, $title);
            $partner = $chatHead->getChatPartner($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Chat retrieved/created successfully',
                'data' => [
                    'chat_id' => $chatHead->chat_id,
                    'id' => $chatHead->id,
                    'partner' => $partner,
                    'service' => $chatHead->service ? [
                        'id' => $chatHead->service->id,
                        'title' => $chatHead->service->title,
                        'image' => $chatHead->service->image_url,
                    ] : null,
                    'chat_type' => $chatHead->chat_type,
                    'title' => $chatHead->title,
                    'is_archived' => $chatHead->isArchivedFor($userId),
                    'is_muted' => $chatHead->isMutedFor($userId),
                    'unread_count' => $chatHead->getUnreadCount($userId),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error creating/retrieving chat: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get messages for a chat
     */
    public function getMessages(Request $request, $chatId)
    {
        try {
            $userId = Auth::id();
            
            // Verify user has access to this chat
            $chatHead = ChatHead::where('chat_id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                          ->orWhere('user2_id', $userId);
                })->first();

            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $limit = $request->get('limit', 50);
            $beforeMessageId = $request->get('before_message_id');

            $messages = ChatMessage::getChatMessages($chatId, $limit, $beforeMessageId);

            // Format messages for response
            $formattedMessages = $messages->map(function ($message) {
                return $message->getFormattedMessage();
            });

            // Mark messages as read if they're for this user
            ChatMessage::where('chat_id', $chatId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            // Update unread count in chat head
            $chatHead->markAsRead($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Messages retrieved successfully',
                'data' => [
                    'messages' => $formattedMessages,
                    'has_more' => $messages->count() === $limit,
                    'chat_info' => [
                        'chat_id' => $chatHead->chat_id,
                        'partner' => $chatHead->getChatPartner($userId),
                        'service' => $chatHead->service ? [
                            'id' => $chatHead->service->id,
                            'title' => $chatHead->service->title,
                            'image' => $chatHead->service->image_url,
                        ] : null,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving messages: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, $chatId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string',
                'type' => 'in:text,image,video,voice,file',
                'reply_to_message_id' => 'nullable|string|exists:chat_messages_2,message_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            
            // Verify user has access to this chat
            $chatHead = ChatHead::where('chat_id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                          ->orWhere('user2_id', $userId);
                })->first();

            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            // Determine receiver
            $receiverId = ($userId == $chatHead->user1_id) ? $chatHead->user2_id : $chatHead->user1_id;

            $messageData = [
                'chat_id' => $chatId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'message_type' => $request->get('type', 'text'),
                'message_content' => $request->content,
                'reply_to_message_id' => $request->reply_to_message_id,
            ];

            $message = ChatMessage::create($messageData);

            return response()->json([
                'code' => 1,
                'message' => 'Message sent successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error sending message: ' . $e->getMessage(),
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
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $message = ChatMessage::where('message_id', $messageId)->first();

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            if (!$message->canBeEdited($userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Cannot edit this message',
                    'data' => null
                ], 403);
            }

            $message->editContent($request->content);

            return response()->json([
                'code' => 1,
                'message' => 'Message edited successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error editing message: ' . $e->getMessage(),
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
            $message = ChatMessage::where('message_id', $messageId)->first();

            if (!$message) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            if (!$message->canBeDeleted($userId)) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Cannot delete this message',
                    'data' => null
                ], 403);
            }

            $message->softDelete();

            return response()->json([
                'code' => 1,
                'message' => 'Message deleted successfully',
                'data' => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error deleting message: ' . $e->getMessage(),
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
                ], 400);
            }

            $userId = Auth::id();
            $message = ChatMessage::where('message_id', $messageId)->first();

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
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error adding reaction: ' . $e->getMessage(),
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
            $message = ChatMessage::where('message_id', $messageId)->first();

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
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error removing reaction: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Archive/unarchive chat
     */
    public function toggleArchive($chatId)
    {
        try {
            $userId = Auth::id();
            
            $chatHead = ChatHead::where('chat_id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                          ->orWhere('user2_id', $userId);
                })->first();

            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleArchive($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Chat archive status updated successfully',
                'data' => [
                    'is_archived' => $chatHead->isArchivedFor($userId)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error updating archive status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Mute/unmute chat
     */
    public function toggleMute($chatId)
    {
        try {
            $userId = Auth::id();
            
            $chatHead = ChatHead::where('chat_id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                          ->orWhere('user2_id', $userId);
                })->first();

            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleMute($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Chat mute status updated successfully',
                'data' => [
                    'is_muted' => $chatHead->isMutedFor($userId)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error updating mute status: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Search messages in chat
     */
    public function searchMessages(Request $request, $chatId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            
            // Verify user has access to this chat
            $chatHead = ChatHead::where('chat_id', $chatId)
                ->where(function ($query) use ($userId) {
                    $query->where('user1_id', $userId)
                          ->orWhere('user2_id', $userId);
                })->first();

            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $messages = ChatMessage::searchInChat($chatId, $request->query);

            $formattedMessages = $messages->map(function ($message) {
                return $message->getFormattedMessage();
            });

            return response()->json([
                'code' => 1,
                'message' => 'Search completed successfully',
                'data' => [
                    'messages' => $formattedMessages,
                    'total' => $formattedMessages->count(),
                    'query' => $request->query,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error searching messages: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Upload media file for chat
     */
    public function uploadMedia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:50240', // 50MB max
                'type' => 'required|in:image,video,voice,file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $file = $request->file('file');
            $type = $request->type;
            
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Store file in appropriate directory
            $path = $file->storeAs("chat/{$type}s", $filename, 'public');
            
            $mediaData = [
                'url' => Storage::url($path),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $type,
                'mime_type' => $file->getMimeType(),
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Media uploaded successfully',
                'data' => $mediaData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error uploading media: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Legacy: Get chat messages (mobile app compatibility)
     */
    public function getChatMessages(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            $chatHeadId = $request->get('chat_head_id');
            
            if (!$userId || !$chatHeadId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Missing required parameters: user_id and chat_head_id',
                    'data' => []
                ], 400);
            }

            // Find chat head by ID
            $chatHead = ChatHead::find($chatHeadId);
            
            if (!$chatHead) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Chat head not found',
                    'data' => []
                ], 404);
            }

            // Verify user has access to this chat
            if ($chatHead->user_1_id != $userId && $chatHead->user_2_id != $userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Access denied to this chat',
                    'data' => []
                ], 403);
            }

            // Get messages for this chat
            $messages = ChatMessage::where('chat_head_id', $chatHead->id)
                ->where('is_deleted', false)
                ->orderBy('created_at', 'asc')
                ->get();

            // Format messages for mobile app
            $formattedMessages = $messages->map(function ($message) use ($userId, $chatHead) {
                return [
                    'id' => $message->id,
                    'chat_head_id' => $chatHead->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'message' => $message->body ?? '',
                    'body' => $message->body ?? '',
                    'message_type' => $message->type ?? 'text',
                    'type' => $message->type ?? 'text',
                    'attachment_url' => $message->image_url ?? $message->audio_url ?? $message->video_url ?? $message->document_url ?? '',
                    'message_status' => $message->status ?? 'sent',
                    'status' => $message->status ?? 'sent',
                    'created_at' => $message->created_at ? $message->created_at->toDateTimeString() : null,
                    'updated_at' => $message->updated_at ? $message->updated_at->toDateTimeString() : null,
                    'is_mine' => $message->sender_id == $userId,
                ];
            });

            // Mark messages as read for this user
            ChatMessage::where('chat_head_id', $chatHead->id)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                ]);

            // Update unread count in chat head
            if ($chatHead->user_1_id == $userId) {
                $chatHead->update(['user_1_unread_count' => 0]);
            } else {
                $chatHead->update(['user_2_unread_count' => 0]);
            }

            return response()->json([
                'code' => 1,
                'message' => 'Messages retrieved successfully',
                'data' => $formattedMessages->toArray()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving messages: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Legacy: Send message (mobile app compatibility)
     */
    public function sendMessageLegacy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sender_id' => 'required|integer',
                'receiver_id' => 'required|integer',
                'message' => 'required|string',
                'message_type' => 'nullable|string',
                'attachment_url' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $senderId = $request->sender_id;
            $receiverId = $request->receiver_id;
            $messageText = $request->message;
            $messageType = $request->message_type ?? 'text';
            $attachmentUrl = $request->attachment_url ?? '';

            // Get or create chat head
            $chatHead = ChatHead::getOrCreateChatHead($senderId, $receiverId);

            // Create message
            $messageData = [
                'chat_head_id' => $chatHead->id,
                'chat_id' => $chatHead->chat_id,
                'message_id' => 'msg_' . Str::uuid(),
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'user_1_id' => $senderId,
                'user_2_id' => $receiverId,
                'type' => $messageType,
                'body' => $messageText,
                'status' => 'sent',
                'delivered_at' => now(),
            ];

            // Add attachment URLs based on type
            if ($attachmentUrl) {
                if ($messageType == 'image') {
                    $messageData['image_url'] = $attachmentUrl;
                } elseif ($messageType == 'audio') {
                    $messageData['audio_url'] = $attachmentUrl;
                } elseif ($messageType == 'video') {
                    $messageData['video_url'] = $attachmentUrl;
                } elseif ($messageType == 'document') {
                    $messageData['document_url'] = $attachmentUrl;
                }
            }

            $message = ChatMessage::create($messageData);

            // Format response for mobile app
            $responseMessage = [
                'id' => $message->id,
                'chat_head_id' => $chatHead->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message' => $message->body,
                'message_type' => $message->type,
                'attachment_url' => $message->image_url ?? $message->audio_url ?? $message->video_url ?? $message->document_url ?? '',
                'message_status' => $message->status,
                'created_at' => $message->created_at->toISOString(),
                'updated_at' => $message->updated_at->toISOString(),
                'is_mine' => true,
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Message sent successfully',
                'data' => $responseMessage
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error sending message: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Legacy: Get user's chats (mobile app compatibility)
     */
    public function getMyChats(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Missing user_id parameter',
                    'data' => []
                ], 400);
            }

            $chats = ChatHead::getUserChats($userId, false);

            // Format chats for mobile app
            $formattedChats = $chats->map(function ($chat) use ($userId) {
                $partner = $chat->getChatPartner($userId);
                
                return [
                    'id' => $chat->id,
                    'sender_id' => $userId, // Current user is always sender in this context
                    'receiver_id' => $partner ? $partner['id'] : null,
                    'last_message' => $chat->last_message ?? '',
                    'last_message_time' => $chat->last_message_at ? $chat->last_message_at->toISOString() : '',
                    'unread_count' => $chat->getUnreadCount($userId),
                    'receiver_name' => $partner ? $partner['name'] : 'Unknown User',
                    'receiver_avatar' => $partner ? $partner['avatar'] : '',
                    'receiver_phone' => '', // Not available in current schema
                    'last_seen' => '', // Not available in current schema
                    'chat_status' => $chat->is_active ? 'active' : 'inactive',
                    'created_at' => $chat->created_at->toISOString(),
                    'updated_at' => $chat->updated_at->toISOString(),
                ];
            });

            return $formattedChats;

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error retrieving chats: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Helper: Get message status for mobile app compatibility
     */
    private function getMessageStatus($message)
    {
        if ($message->is_read && $message->read_at) {
            return 'read';
        } elseif ($message->is_delivered && $message->delivered_at) {
            return 'delivered';
        } else {
            return 'sent';
        }
    }

    /**
     * Legacy: Create or get chat head for mobile app
     */
    public function createOrGetChatHead(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sender_id' => 'required|integer|exists:admin_users,id',
                'receiver_id' => 'required|integer|exists:admin_users,id',
                'receiver_name' => 'nullable|string',
                'service_id' => 'nullable|integer|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $senderId = $request->sender_id;
            $receiverId = $request->receiver_id;
            $serviceId = $request->service_id;

            if ($senderId == $receiverId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Cannot create chat with yourself',
                    'data' => null
                ], 400);
            }

            $chatHead = ChatHead::getOrCreateChatHead($senderId, $receiverId, $serviceId);
            $partner = $chatHead->getChatPartner($senderId);

            // Format for mobile app
            $formattedChatHead = [
                'id' => $chatHead->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'receiver_name' => $partner ? $partner['name'] : $request->receiver_name ?? 'Unknown User',
                'receiver_avatar' => $partner ? $partner['avatar'] : '',
                'receiver_phone' => '',
                'last_message' => $chatHead->last_message ?? '',
                'last_message_time' => $chatHead->last_message_at ? $chatHead->last_message_at->toISOString() : '',
                'unread_count' => $chatHead->getUnreadCount($senderId),
                'chat_status' => $chatHead->is_active ? 'active' : 'inactive',
                'created_at' => $chatHead->created_at->toISOString(),
                'updated_at' => $chatHead->updated_at->toISOString(),
            ];

            return response()->json([
                'code' => 1,
                'message' => 'Chat head created/retrieved successfully',
                'data' => $formattedChatHead
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error creating chat head: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
