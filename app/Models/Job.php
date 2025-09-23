<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Job extends Model
{
    use HasFactory;

    protected $casts = [
        'deadline' => 'datetime',
        'minimum_salary' => 'float',
        'maximum_salary' => 'float',
        'vacancies_count' => 'integer',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'required_video_cv' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            //decode the html specilar chars for title
            $job->title = html_entity_decode($job->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $job->slug = self::generateUniqueSlug($job->title);
        });

        static::updated(function ($job) {
            $original = $job->getOriginal();

            // Update counts for both previous and new category/district
            if ($job->wasChanged('category_id')) {
                self::updateCategoryCount($original['category_id']);
                self::updateCategoryCount($job->category_id);
            }

            if ($job->wasChanged('district_id')) {
                self::updateDistrictCount($original['district_id']);
                self::updateDistrictCount($job->district_id);
            }
        });

        static::deleted(function ($job) {
            self::updateCategoryCount($job->category_id);
            self::updateDistrictCount($job->district_id);
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = static::where('slug', $slug)->count();

        return $count ? "{$slug}-" . Str::lower(Str::random(4)) : $slug;
    }

    public static function updateCategoryCount(?int $categoryId): void
    {
        if (!$categoryId) return;

        $count = static::where('category_id', $categoryId)
            ->where('status', 'Active')
            ->count();

        JobCategory::where('id', $categoryId)->update(['jobs_count' => $count]);
    }

    public static function updateDistrictCount(?int $districtId): void
    {
        if (!$districtId) return;

        $count = static::where('district_id', $districtId)
            ->where('status', 'Active')
            ->count();

        District::where('id', $districtId)->update(['jobs_count' => $count]);
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeFilterBySalary($query, $min, $max)
    {
        return $query->when($min, fn($q) => $q->where('minimum_salary', '>=', $min))
            ->when($max, fn($q) => $q->where('maximum_salary', '<=', $max));
    }

    public function scopeFilterByAge($query, $min, $max)
    {
        return $query->when($min, fn($q) => $q->where('min_age', '>=', $min))
            ->when($max, fn($q) => $q->where('max_age', '<=', $max));
    }

    public function scopeWithVideoCv($query)
    {
        return $query->where('required_video_cv', true);
    }

    //appeds district_text
    public function getDistrictTextAttribute()
    {
        if ($this->district == null) return '';
        return $this->district->name;
    }

    //appeds category_text
    public function getCategoryTextAttribute()
    {
        if ($this->category == null) return '';
        return $this->category->name;
    }

    //make getter for title to decode html special chars
    public function getTitleAttribute($value)
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected $appends = ['district_text', 'category_text'];
}
