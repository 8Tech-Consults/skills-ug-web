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
                    'success' => false,
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
                'success' => true,
                'message' => 'Chats retrieved successfully',
                'data' => [
                    'chats' => $formattedChats,
                    'total' => $formattedChats->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                'partner_id' => 'required|integer|exists:users,id',
                'service_id' => 'nullable|integer|exists:services,id',
                'title' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
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
                    'success' => false,
                    'message' => 'Cannot create chat with yourself',
                    'data' => null
                ], 400);
            }

            $chatHead = ChatHead::getOrCreateChatHead($userId, $partnerId, $serviceId, $title);
            $partner = $chatHead->getChatPartner($userId);

            return response()->json([
                'success' => true,
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
                'success' => false,
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
                    'success' => false,
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
                'success' => true,
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
                'success' => false,
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
                    'success' => false,
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
                    'success' => false,
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
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $message = ChatMessage::where('message_id', $messageId)->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            if (!$message->canBeEdited($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit this message',
                    'data' => null
                ], 403);
            }

            $message->editContent($request->content);

            return response()->json([
                'success' => true,
                'message' => 'Message edited successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            if (!$message->canBeDeleted($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this message',
                    'data' => null
                ], 403);
            }

            $message->softDelete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
                'data' => null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $userId = Auth::id();
            $message = ChatMessage::where('message_id', $messageId)->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            $message->addReaction($userId, $request->emoji);

            return response()->json([
                'success' => true,
                'message' => 'Reaction added successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Message not found',
                    'data' => null
                ], 404);
            }

            $message->removeReaction($userId);

            return response()->json([
                'success' => true,
                'message' => 'Reaction removed successfully',
                'data' => [
                    'message' => $message->getFormattedMessage()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleArchive($userId);

            return response()->json([
                'success' => true,
                'message' => 'Chat archive status updated successfully',
                'data' => [
                    'is_archived' => $chatHead->isArchivedFor($userId)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $chatHead->toggleMute($userId);

            return response()->json([
                'success' => true,
                'message' => 'Chat mute status updated successfully',
                'data' => [
                    'is_muted' => $chatHead->isMutedFor($userId)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
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
                    'success' => false,
                    'message' => 'Chat not found or access denied',
                    'data' => null
                ], 404);
            }

            $messages = ChatMessage::searchInChat($chatId, $request->query);

            $formattedMessages = $messages->map(function ($message) {
                return $message->getFormattedMessage();
            });

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully',
                'data' => [
                    'messages' => $formattedMessages,
                    'total' => $formattedMessages->count(),
                    'query' => $request->query,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
                    'success' => false,
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
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => $mediaData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading media: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
