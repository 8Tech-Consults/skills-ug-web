<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseQuiz Model - Represents quizzes for course units
 * Contains questions, options, correct answers, and quiz settings
 */
class CourseQuiz extends Model
{
    use HasFactory;

    protected $table = 'course_quizzes';

    protected $fillable = [
        'unit_id',
        'title',
        'description',
        'questions',
        'passing_score',
        'time_limit_minutes',
        'max_attempts',
        'status',
    ];

    protected $casts = [
        'questions' => 'array',
        'passing_score' => 'integer',
        'time_limit_minutes' => 'integer',
        'max_attempts' => 'integer',
    ];

    /**
     * Get the unit that owns the quiz
     */
    public function unit()
    {
        return $this->belongsTo(CourseUnit::class, 'unit_id');
    }

    /**
     * Get quiz answers
     */
    public function answers()
    {
        return $this->hasMany(CourseQuizAnswer::class, 'quiz_id');
    }

    /**
     * Scope for active quizzes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get time limit in seconds
     */
    public function getTimeLimitSecondsAttribute()
    {
        return $this->time_limit_minutes * 60;
    }

    /**
     * Check if quiz has unlimited attempts
     */
    public function getHasUnlimitedAttemptsAttribute()
    {
        return $this->max_attempts == 0;
    }

    /**
     * Calculate score for given answers
     */
    public function calculateScore($userAnswers)
    {
        if (empty($this->questions) || empty($userAnswers)) {
            return 0;
        }
        
        $correctAnswers = 0;
        $totalQuestions = count($this->questions);
        
        foreach ($this->questions as $index => $question) {
            if (isset($userAnswers[$index]) && 
                isset($question['correct_answer']) && 
                $userAnswers[$index] === $question['correct_answer']) {
                $correctAnswers++;
            }
        }
        
        return round(($correctAnswers / $totalQuestions) * 100);
    }

    /**
     * Check if score is passing
     */
    public function isScorePassing($score)
    {
        return $score >= $this->passing_score;
    }
}