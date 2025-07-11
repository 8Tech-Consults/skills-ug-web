<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseUnit Model - Represents individual units/chapters within a course
 * Each course is divided into units for better organization
 */
class CourseUnit extends Model
{
    use HasFactory;

    protected $table = 'course_units';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'duration_minutes',
        'is_preview',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'duration_minutes' => 'integer',
    ];

    /**
     * Get the course that owns the unit
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get unit materials
     */
    public function materials()
    {
        return $this->hasMany(CourseMaterial::class, 'unit_id')->orderBy('sort_order');
    }

    /**
     * Get unit quizzes
     */
    public function quizzes()
    {
        return $this->hasMany(CourseQuiz::class, 'unit_id');
    }

    /**
     * Get unit progress records
     */
    public function progress()
    {
        return $this->hasMany(CourseProgress::class, 'unit_id');
    }

    /**
     * Scope for active units
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for preview units
     */
    public function scopePreview($query)
    {
        return $query->where('is_preview', 'Yes');
    }

    /**
     * Check if unit is preview
     */
    public function getIsPreviewUnitAttribute()
    {
        return $this->is_preview === 'Yes';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if ($this->duration_minutes == 0) {
            return 'Duration not specified';
        }
        
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . 'm';
        }
        
        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($minutes == 0) {
            return $hours . 'h';
        }
        
        return $hours . 'h ' . $minutes . 'm';
    }
}