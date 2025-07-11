<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseQuizAnswer Model - Represents user's answers to course quizzes
 * Tracks quiz attempts, scores, and detailed answer history
 */
class CourseQuizAnswer extends Model
{
    use HasFactory;

    protected $table = 'course_quiz_answers';

    protected $fillable = [
        'user_id',
        'quiz_id',
        'answers',
        'score',
        'passed',
        'attempt_number',
        'time_taken_seconds',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'attempt_number' => 'integer',
        'time_taken_seconds' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the quiz answer
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the quiz that owns the answer
     */
    public function quiz()
    {
        return $this->belongsTo(CourseQuiz::class, 'quiz_id');
    }

    /**
     * Check if the attempt passed
     */
    public function getIsPassedAttribute()
    {
        return $this->passed === 'Yes';
    }

    /**
     * Get formatted time taken
     */
    public function getFormattedTimeTakenAttribute()
    {
        if ($this->time_taken_seconds < 60) {
            return $this->time_taken_seconds . 's';
        }
        
        $minutes = intval($this->time_taken_seconds / 60);
        $seconds = $this->time_taken_seconds % 60;
        
        if ($seconds == 0) {
            return $minutes . 'm';
        }
        
        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Get formatted score with percentage
     */
    public function getFormattedScoreAttribute()
    {
        return $this->score . '%';
    }

    /**
     * Scope for passed attempts
     */
    public function scopePassed($query)
    {
        return $query->where('passed', 'Yes');
    }

    /**
     * Scope for failed attempts
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', 'No');
    }

    /**
     * Get user's best score for a quiz
     */
    public static function getBestScore($userId, $quizId)
    {
        return static::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->max('score') ?? 0;
    }

    /**
     * Get user's attempt count for a quiz
     */
    public static function getAttemptCount($userId, $quizId)
    {
        return static::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->count();
    }
}