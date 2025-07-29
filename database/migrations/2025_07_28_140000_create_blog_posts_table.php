<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('category')->nullable();
            $table->json('tags')->nullable(); // Store tags as JSON array
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->boolean('featured')->default(false);
            $table->datetime('published_at')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->integer('reading_time_minutes')->nullable(); // Estimated reading time
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['status', 'published_at']);
            $table->index(['category', 'status']);
            $table->index(['featured', 'status']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
