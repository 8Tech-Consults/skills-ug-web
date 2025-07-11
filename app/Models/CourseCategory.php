<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseCategory Model - Represents course categories for Eight Learning module
 * Categories like Programming, Design, Marketing, etc.
 */
class CourseCategory extends Model
{
    use HasFactory;

    protected $table = 'course_categories';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get courses in this category
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }

    /**
     * Get active courses count
     */
    public function getCourseCountAttribute()
    {
        return $this->courses()->where('status', 'active')->count();
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }
}