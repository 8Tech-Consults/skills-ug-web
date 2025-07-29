<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author_name',
        'author_email',
        'status',
        'category',
        'tags',
        'views_count',
        'likes_count',
        'featured',
        'published_at',
        'meta_description',
        'meta_keywords',
        'reading_time_minutes',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'featured' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'reading_time_minutes' => 'integer',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
    ];

    // Automatically generate slug when creating/updating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            // Calculate reading time (average 200 words per minute)
            if (!empty($post->content)) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time_minutes = max(1, ceil($wordCount / 200));
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && empty($post->getOriginal('slug'))) {
                $post->slug = Str::slug($post->title);
            }

            // Recalculate reading time if content changed
            if ($post->isDirty('content')) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time_minutes = max(1, ceil($wordCount / 200));
            }
        });
    }

    // Scopes for common queries
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', Carbon::now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeByCategory($query, $category)
    {
        if (empty($category)) {
            return $query;
        }
        return $query->where('category', $category);
    }

    public function scopeByTag($query, $tag)
    {
        if (empty($tag)) {
            return $query;
        }
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'LIKE', "%{$search}%")
                ->orWhere('excerpt', 'LIKE', "%{$search}%")
                ->orWhere('content', 'LIKE', "%{$search}%")
                ->orWhere('category', 'LIKE', "%{$search}%");
        });
    }

    // Accessors and mutators
    public function getExcerptAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        // Auto-generate excerpt from content if not provided
        return Str::limit(strip_tags($this->content), 160);
    }

    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image;
        if (empty($this->featured_image)) {
            return null;
        }

        if (Str::startsWith($this->featured_image, ['http://', 'https://'])) {
            return $this->featured_image;
        }

        return config('app.url') . '/storage/images/' . basename($this->featured_image);
    }

    public function getFormattedPublishedAtAttribute()
    {
        if (!$this->published_at) {
            return null;
        }

        return $this->published_at->format('M d, Y');
    }

    public function getReadingTimeTextAttribute()
    {
        if (!$this->reading_time_minutes) {
            return '1 min read';
        }

        return $this->reading_time_minutes . ' min read';
    }

    // Helper methods
    public function isPublished()
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at <= Carbon::now();
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function incrementLikes()
    {
        $this->increment('likes_count');
    }

    public function getUrl()
    {
        return "/blog/{$this->slug}";
    }

    // Static methods for common operations
    public static function getCategories()
    {
        return self::published()
            ->select('category')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();
    }

    public static function getAllTags()
    {
        $posts = self::published()
            ->whereNotNull('tags')
            ->select('tags')
            ->get();

        $allTags = [];
        foreach ($posts as $post) {
            if (is_array($post->tags)) {
                $allTags = array_merge($allTags, $post->tags);
            }
        }

        return collect($allTags)->unique()->sort()->values();
    }

    public static function getFeaturedPosts($limit = 5)
    {
        return self::published()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getLatestPosts($limit = 10)
    {
        return self::published()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getPopularPosts($limit = 10)
    {
        return self::published()
            ->orderBy('views_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
