<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CourseCertificate Model - Represents course completion certificates
 * Manages certificate generation, verification, and sharing for Eight Learning
 */
class CourseCertificate extends Model
{
    use HasFactory;

    protected $table = 'course_certificates';

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'issued_date',
        'completion_date',
        'grade',
        'pdf_url',
        'verification_code',
        'status',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'issued_date' => 'datetime',
        'completion_date' => 'datetime',
    ];

    /**
     * Get the user that owns the certificate
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the certificate
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Check if certificate is active
     */
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    /**
     * Check if certificate is revoked
     */
    public function getIsRevokedAttribute()
    {
        return $this->status === 'revoked';
    }

    /**
     * Check if certificate has PDF
     */
    public function getHasPdfAttribute()
    {
        return !empty($this->pdf_url);
    }

    /**
     * Get grade letter
     */
    public function getGradeLetterAttribute()
    {
        if ($this->grade >= 90) return 'A+';
        if ($this->grade >= 85) return 'A';
        if ($this->grade >= 80) return 'A-';
        if ($this->grade >= 75) return 'B+';
        if ($this->grade >= 70) return 'B';
        if ($this->grade >= 65) return 'B-';
        if ($this->grade >= 60) return 'C+';
        if ($this->grade >= 55) return 'C';
        if ($this->grade >= 50) return 'C-';
        return 'F';
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrlAttribute()
    {
        return "https://eightlearning.com/verify/{$this->verification_code}";
    }

    /**
     * Get status display text
     */
    public function getStatusTextAttribute()
    {
        switch (strtolower($this->status)) {
            case 'active':
                return 'Active';
            case 'revoked':
                return 'Revoked';
            case 'expired':
                return 'Expired';
            default:
                return $this->status;
        }
    }

    /**
     * Scope for active certificates
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber()
    {
        $date = now()->format('Ymd');
        $timestamp = now()->timestamp;
        $uniqueId = substr($timestamp, -6);
        return "EL-CERT-$date-$uniqueId";
    }

    /**
     * Generate verification code
     */
    public static function generateVerificationCode()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Create new certificate
     */
    public static function createCertificate($data)
    {
        $data['certificate_number'] = static::generateCertificateNumber();
        $data['verification_code'] = static::generateVerificationCode();
        $data['issued_date'] = now();
        $data['completion_date'] = $data['completion_date'] ?? now();
        $data['status'] = $data['status'] ?? 'active';
        
        return static::create($data);
    }

    /**
     * Check if user has certificate for course
     */
    public static function hasUserCompletedCourse($userId, $courseId)
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get certificate by verification code
     */
    public static function getByVerificationCode($verificationCode)
    {
        return static::where('verification_code', $verificationCode)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Verify certificate authenticity
     */
    public static function verifyCertificate($verificationCode)
    {
        $certificate = static::getByVerificationCode($verificationCode);
        
        if (!$certificate) {
            return [
                'valid' => false,
                'message' => 'Certificate not found or invalid verification code.',
            ];
        }

        if (!$certificate->is_active) {
            return [
                'valid' => false,
                'message' => 'Certificate has been revoked or is no longer active.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Certificate is valid and authentic.',
            'certificate' => $certificate,
        ];
    }

    /**
     * Get user's certificate statistics
     */
    public static function getUserCertificateStats($userId)
    {
        $certificates = static::where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        if ($certificates->isEmpty()) {
            return [
                'total_certificates' => 0,
                'average_grade' => 0,
                'categories_completed' => 0,
                'latest_certificate' => null,
            ];
        }

        $totalCertificates = $certificates->count();
        $averageGrade = $certificates->avg('grade');
        
        // Get unique categories
        $categories = $certificates->load('course.category')
            ->pluck('course.category.name')
            ->unique()
            ->count();

        // Get latest certificate
        $latestCertificate = $certificates->sortByDesc('issued_date')->first();

        return [
            'total_certificates' => $totalCertificates,
            'average_grade' => round($averageGrade, 2),
            'categories_completed' => $categories,
            'latest_certificate' => $latestCertificate,
        ];
    }

    /**
     * Get sharing text for social media
     */
    public function getSharingTextAttribute()
    {
        return "🎉 I just completed '{$this->course->title}' on Eight Learning and earned my certificate! " .
               "Grade: {$this->grade_letter} ({$this->grade}%) " .
               "Verify at: {$this->verification_url} " .
               "#EightLearning #OnlineLearning #Certificate #Achievement";
    }
}