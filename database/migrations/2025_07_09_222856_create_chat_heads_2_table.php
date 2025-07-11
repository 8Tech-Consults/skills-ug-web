<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatHeads2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_heads_2', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique(); // Unique identifier for the chat
            $table->integer('user1_id'); // First participant
            $table->integer('user2_id'); // Second participant
            $table->integer('service_id')->nullable(); // Related service (nullable for non-service chats)
            $table->string('chat_type')->default('direct'); // direct, group, service
            $table->string('title')->nullable(); // Chat title (for groups or custom names)
            $table->text('last_message')->nullable(); // Preview of last message
            $table->integer('last_message_user_id')->nullable(); // Who sent the last message
            $table->timestamp('last_message_at')->nullable(); // When was the last message
            $table->integer('unread_count_user1')->default(0); // Unread count for user1
            $table->integer('unread_count_user2')->default(0); // Unread count for user2
            $table->boolean('is_archived_user1')->default(false); // Archive status for user1
            $table->boolean('is_archived_user2')->default(false); // Archive status for user2
            $table->boolean('is_muted_user1')->default(false); // Mute status for user1
            $table->boolean('is_muted_user2')->default(false); // Mute status for user2
            $table->boolean('is_active')->default(true); // Chat is active
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user1_id', 'user2_id']);
            $table->index('service_id');
            $table->index('chat_type');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_heads_2');
    }
}
