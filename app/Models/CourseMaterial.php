<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseMaterial Model - Represents learning materials within course units
 * Supports multiple formats: video, audio, text, PDF, images
 */
class CourseMaterial extends Model
{
    use HasFactory;

    protected $table = 'course_materials';

    protected $fillable = [
        'unit_id',
        'title',
        'type',
        'content_source',
        'content_url',
        'external_url',
        'content_text',
        'duration_seconds',
        'file_size',
        'sort_order',
        'is_downloadable',
        'status',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the unit that owns the material
     */
    public function unit()
    {
        return $this->belongsTo(CourseUnit::class, 'unit_id');
    }

    /**
     * Get material progress records
     */
    public function progress()
    {
        return $this->hasMany(CourseProgress::class, 'material_id');
    }

    /**
     * Scope for active materials
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for downloadable materials
     */
    public function scopeDownloadable($query)
    {
        return $query->where('is_downloadable', 'Yes');
    }

    /**
     * Check if material is downloadable
     */
    public function getIsDownloadableFileAttribute()
    {
        return $this->is_downloadable === 'Yes';
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if ($this->duration_seconds == 0) {
            return '';
        }
        
        if ($this->duration_seconds < 60) {
            return $this->duration_seconds . 's';
        } elseif ($this->duration_seconds < 3600) {
            $minutes = intval($this->duration_seconds / 60);
            $seconds = $this->duration_seconds % 60;
            if ($seconds == 0) {
                return $minutes . 'm';
            }
            return $minutes . 'm ' . $seconds . 's';
        } else {
            $hours = intval($this->duration_seconds / 3600);
            $minutes = intval(($this->duration_seconds % 3600) / 60);
            if ($minutes == 0) {
                return $hours . 'h';
            }
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute()
    {
        if ($this->file_size == 0) {
            return '';
        }
        
        if ($this->file_size < 1024) {
            return $this->file_size . 'B';
        } elseif ($this->file_size < 1024 * 1024) {
            return round($this->file_size / 1024, 1) . 'KB';
        } elseif ($this->file_size < 1024 * 1024 * 1024) {
            return round($this->file_size / (1024 * 1024), 1) . 'MB';
        } else {
            return round($this->file_size / (1024 * 1024 * 1024), 1) . 'GB';
        }
    }

    /**
     * Get material type icon
     */
    public function getTypeIconAttribute()
    {
        switch (strtolower($this->type)) {
            case 'video':
                return '🎥';
            case 'audio':
                return '🎵';
            case 'text':
                return '📄';
            case 'pdf':
                return '📋';
            case 'image':
                return '🖼️';
            default:
                return '📄';
        }
    }

    /**
     * Get effective content URL based on source type
     */
    public function getEffectiveContentUrlAttribute()
    {
        if ($this->content_source === 'external' && !empty($this->external_url)) {
            return $this->external_url;
        }
        
        return $this->content_url ?? '';
    }

    /**
     * Check if material is external source
     */
    public function getIsExternalSourceAttribute()
    {
        return strtolower($this->content_source) === 'external';
    }

    /**
     * Check if material is file source
     */
    public function getIsFileSourceAttribute()
    {
        return strtolower($this->content_source) === 'file';
    }

    /**
     * Detect content type based on URL and extension
     */
    public function getDetectedContentTypeAttribute()
    {
        if ($this->type === 'quiz') {
            return 'quiz';
        }

        $url = strtolower($this->effective_content_url);
        
        // YouTube detection
        if (strpos($url, 'youtube.com') !== false || 
            strpos($url, 'youtu.be') !== false ||
            strpos($url, 'vimeo.com') !== false) {
            return 'video';
        }

        // Video extensions
        $videoExts = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.webm', '.mkv', '.m4v', '.3gp', '.ts', '.m3u8'];
        foreach ($videoExts as $ext) {
            if (str_ends_with($url, $ext)) {
                return 'video';
            }
        }

        // Audio extensions
        $audioExts = ['.mp3', '.wav', '.aac', '.flac', '.ogg', '.m4a'];
        foreach ($audioExts as $ext) {
            if (str_ends_with($url, $ext)) {
                return 'audio';
            }
        }

        // PDF
        if (str_ends_with($url, '.pdf')) {
            return 'pdf';
        }

        // Image extensions
        $imageExts = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.svg', '.webp'];
        foreach ($imageExts as $ext) {
            if (str_ends_with($url, $ext)) {
                return 'image';
            }
        }

        // Document extensions
        $docExts = ['.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.txt', '.rtf'];
        foreach ($docExts as $ext) {
            if (str_ends_with($url, $ext)) {
                return 'document';
            }
        }

        // External link without specific extension
        if ($this->is_external_source) {
            return 'external_link';
        }

        // Default for text content
        if (!empty($this->content_text)) {
            return 'text';
        }

        return 'document';
    }
}