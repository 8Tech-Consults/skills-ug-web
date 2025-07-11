<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatHeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('chat_heads_2');
        Schema::create('chat_heads_2', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Participants
            $table->unsignedBigInteger('user_1_id');
            $table->unsignedBigInteger('user_2_id');
            $table->string('user_1_name')->nullable();
            $table->string('user_2_name')->nullable();
            $table->text('user_1_photo')->nullable();
            $table->text('user_2_photo')->nullable();

            // Last seen tracking
            $table->timestamp('user_1_last_seen')->nullable();
            $table->timestamp('user_2_last_seen')->nullable();

            // Last message details
            $table->unsignedBigInteger('last_message_sent_by_user_id')->nullable();
            $table->text('last_message_body')->nullable();
            $table->timestamp('last_message_time')->nullable();
            $table->string('last_message_status')->nullable(); // sent, delivered, read
            $table->string('last_message_type')->nullable(); // text, image, video, audio, document, address, gps_location
            $table->unsignedBigInteger('last_message_sender_id')->nullable();
            $table->unsignedBigInteger('last_message_receiver_id')->nullable();

            // Unread counts
            $table->integer('user_1_unread_count')->default(0);
            $table->integer('user_2_unread_count')->default(0);

            // Enhanced features
            $table->string('chat_status')->default('active'); // active, archived, muted, blocked
            $table->string('chat_type')->default('direct'); // direct, service_inquiry
            $table->unsignedBigInteger('related_service_id')->nullable(); // If chat is about a service
            $table->text('chat_subject')->nullable(); // Topic or subject of chat
            $table->string('user_1_notification_preference')->default('enabled'); // enabled, muted, disabled
            $table->string('user_2_notification_preference')->default('enabled');

            // Indexes for performance
            $table->index(['user_1_id', 'user_2_id']);
            $table->index(['user_1_id', 'chat_status']);
            $table->index(['user_2_id', 'chat_status']);
            $table->index('last_message_time');
            $table->index('related_service_id');

            // Ensure unique chat between two users
            $table->unique(['user_1_id', 'user_2_id'], 'unique_user_chat');
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
