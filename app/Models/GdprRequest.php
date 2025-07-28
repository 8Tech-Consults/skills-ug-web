<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    const TYPE_EXPORT = 'export';
    const TYPE_DELETE = 'delete';
    const TYPE_PORTABILITY = 'portability';

    protected $fillable = [
        'user_id',
        'request_type',
        'status',
        'reason',
        'admin_notes',
        'requested_at',
        'processed_at',
        'completed_at',
        'data_file_path',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $dates = [
        'requested_at',
        'processed_at',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that owns the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get requests by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope to get requests by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Mark request as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark request as completed.
     */
    public function markAsCompleted(?string $dataFilePath = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'data_file_path' => $dataFilePath,
        ]);
    }

    /**
     * Mark request as rejected.
     */
    public function markAsRejected(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Create a new GDPR request.
     */
    public static function createRequest(int $userId, string $requestType, ?string $reason = null, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return self::create([
            'user_id' => $userId,
            'request_type' => $requestType,
            'status' => self::STATUS_PENDING,
            'reason' => $reason,
            'requested_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '#ffa500',
            self::STATUS_PROCESSING => '#2196f3',
            self::STATUS_COMPLETED => '#4caf50',
            self::STATUS_REJECTED => '#f44336',
            default => '#9e9e9e',
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Get human-readable request type.
     */
    public function getRequestTypeLabel(): string
    {
        return match($this->request_type) {
            self::TYPE_EXPORT => 'Data Export',
            self::TYPE_DELETE => 'Account Deletion',
            self::TYPE_PORTABILITY => 'Data Portability',
            default => 'Unknown',
        };
    }
}
