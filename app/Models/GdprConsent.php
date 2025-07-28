<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'consent_type',
        'consented',
        'consent_text',
        'version',
        'consented_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consented' => 'boolean',
        'consented_at' => 'datetime',
    ];

    protected $dates = [
        'consented_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user that owns the consent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get consents by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('consent_type', $type);
    }

    /**
     * Scope to get active consents.
     */
    public function scopeConsented($query)
    {
        return $query->where('consented', true);
    }

    /**
     * Check if consent is currently valid.
     */
    public function isValid(): bool
    {
        return $this->consented && $this->consented_at !== null;
    }

    /**
     * Record user consent.
     */
    public static function recordConsent(int $userId, string $consentType, string $consentText, string $version = '1.0', ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'consent_type' => $consentType,
            ],
            [
                'consented' => true,
                'consent_text' => $consentText,
                'version' => $version,
                'consented_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]
        );
    }

    /**
     * Revoke user consent.
     */
    public static function revokeConsent(int $userId, string $consentType): bool
    {
        return self::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->update([
                'consented' => false,
                'consented_at' => null,
            ]);
    }

    /**
     * Get user's consent status for a specific type.
     */
    public static function hasConsent(int $userId, string $consentType): bool
    {
        return self::where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('consented', true)
            ->exists();
    }
}
