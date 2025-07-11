<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('chat_messages_2');

        Schema::create('chat_messages_2', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Chat reference
            $table->unsignedBigInteger('chat_head_id');

            // Message participants
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->unsignedBigInteger('user_1_id');
            $table->unsignedBigInteger('user_2_id');

            // Message content
            $table->string('type')->default('text'); // text, image, video, audio, document, address, gps_location, system
            $table->longText('body')->nullable();
            $table->string('status')->default('sent'); // sent, delivered, read, failed

            // Media attachments
            $table->text('audio_url')->nullable();
            $table->text('video_url')->nullable();
            $table->text('image_url')->nullable();
            $table->text('document_url')->nullable();
            $table->string('document_name')->nullable();
            $table->string('document_size')->nullable();

            // Location data
            $table->text('address')->nullable();
            $table->string('gps_latitude')->nullable();
            $table->string('gps_longitude')->nullable();
            $table->text('gps_location')->nullable(); // JSON formatted location data

            // Enhanced features
            $table->unsignedBigInteger('reply_to_message_id')->nullable(); // For message replies
            $table->text('reply_preview')->nullable(); // Preview of replied message
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('message_priority')->default('normal'); // normal, high, urgent
            $table->text('metadata')->nullable(); // JSON for additional data
            $table->string('encryption_key')->nullable(); // For future encryption
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();

            // Reactions (JSON array of user reactions)
            $table->text('reactions')->nullable(); // JSON: [{"user_id": 1, "emoji": "👍", "timestamp": "..."}]

            // Indexes for performance
            $table->index(['chat_head_id', 'created_at']);
            $table->index(['sender_id', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->index(['user_1_id', 'user_2_id']);
            $table->index('reply_to_message_id');
            $table->index(['type', 'created_at']);
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
