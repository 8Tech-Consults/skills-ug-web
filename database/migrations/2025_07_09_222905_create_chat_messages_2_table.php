<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessages2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_messages_2', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique(); // Unique identifier for the message
            $table->string('chat_id'); // Reference to chat_heads.chat_id
            $table->integer('sender_id'); // Who sent the message
            $table->integer('receiver_id'); // Who should receive the message
            $table->string('message_type')->default('text'); // text, image, file, voice, video
            $table->text('message_content'); // The actual message content
            $table->string('media_url')->nullable(); // URL for media files
            $table->string('media_type')->nullable(); // image, video, audio, document
            $table->integer('media_size')->nullable(); // File size in bytes
            $table->string('thumbnail_url')->nullable(); // Thumbnail for videos/images
            $table->boolean('is_read')->default(false); // Read status
            $table->timestamp('read_at')->nullable(); // When was it read
            $table->boolean('is_delivered')->default(false); // Delivery status
            $table->timestamp('delivered_at')->nullable(); // When was it delivered
            $table->boolean('is_edited')->default(false); // Was the message edited
            $table->timestamp('edited_at')->nullable(); // When was it edited
            $table->boolean('is_deleted')->default(false); // Soft delete
            $table->timestamp('deleted_at')->nullable(); // When was it deleted
            $table->string('reply_to_message_id')->nullable(); // Reply to another message
            $table->json('reactions')->nullable(); // Message reactions
            $table->json('metadata')->nullable(); // Additional metadata
            $table->boolean('is_system_message')->default(false); // System generated message
            $table->timestamps();

            // Indexes for better performance
            $table->index('chat_id');
            $table->index(['sender_id', 'receiver_id']);
            $table->index('message_type');
            $table->index('is_read');
            $table->index('created_at');
            $table->index('reply_to_message_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_messages_2');
    }
}
