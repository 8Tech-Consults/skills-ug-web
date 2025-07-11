<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PaymentReceipt Model - Represents payment receipts for course subscriptions
 * Manages payment records with PDF generation and receipt tracking
 */
class PaymentReceipt extends Model
{
    use HasFactory;

    protected $table = 'payment_receipts';

    protected $fillable = [
        'user_id',
        'course_id',
        'subscription_id',
        'receipt_number',
        'payment_method',
        'amount',
        'currency',
        'transaction_id',
        'payment_date',
        'pdf_url',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    /**
     * Get the user that owns the receipt
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the course that owns the receipt
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Get the subscription that owns the receipt
     */
    public function subscription()
    {
        return $this->belongsTo(CourseSubscription::class, 'subscription_id');
    }

    /**
     * Check if payment is successful
     */
    public function getIsSuccessfulAttribute()
    {
        return $this->status === 'success';
    }

    /**
     * Check if payment is pending
     */
    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment failed
     */
    public function getIsFailedAttribute()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if receipt has PDF
     */
    public function getHasPdfAttribute()
    {
        return !empty($this->pdf_url);
    }

    /**
     * Get formatted payment amount
     */
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get payment method display text
     */
    public function getPaymentMethodTextAttribute()
    {
        switch (strtolower($this->payment_method)) {
            case 'mobile_money':
                return 'Mobile Money';
            case 'credit_card':
                return 'Credit Card';
            case 'debit_card':
                return 'Debit Card';
            case 'bank_transfer':
                return 'Bank Transfer';
            case 'paypal':
                return 'PayPal';
            case 'stripe':
                return 'Stripe';
            default:
                return $this->payment_method;
        }
    }

    /**
     * Get payment method icon
     */
    public function getPaymentMethodIconAttribute()
    {
        switch (strtolower($this->payment_method)) {
            case 'mobile_money':
                return '📱';
            case 'credit_card':
            case 'debit_card':
                return '💳';
            case 'bank_transfer':
                return '🏦';
            case 'paypal':
                return '🅿️';
            case 'stripe':
                return '💳';
            default:
                return '💰';
        }
    }

    /**
     * Get status display text
     */
    public function getStatusTextAttribute()
    {
        switch (strtolower($this->status)) {
            case 'success':
                return 'Successful';
            case 'pending':
                return 'Pending';
            case 'failed':
                return 'Failed';
            case 'cancelled':
                return 'Cancelled';
            case 'refunded':
                return 'Refunded';
            default:
                return $this->status;
        }
    }

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Generate unique receipt number
     */
    public static function generateReceiptNumber()
    {
        $date = now()->format('Ymd');
        $timestamp = now()->timestamp;
        $uniqueId = substr($timestamp, -6);
        return "EL-$date-$uniqueId";
    }

    /**
     * Create new payment receipt
     */
    public static function createReceipt($data)
    {
        $data['receipt_number'] = static::generateReceiptNumber();
        $data['payment_date'] = now();
        $data['status'] = $data['status'] ?? 'pending';
        
        return static::create($data);
    }

    /**
     * Get user's total payments
     */
    public static function getUserTotalPayments($userId)
    {
        return static::where('user_id', $userId)
            ->where('status', 'success')
            ->sum('amount');
    }

    /**
     * Get payment statistics for user
     */
    public static function getUserPaymentStats($userId)
    {
        $receipts = static::where('user_id', $userId)->get();
        $successful = $receipts->where('status', 'success');
        $pending = $receipts->where('status', 'pending');
        $failed = $receipts->where('status', 'failed');
        
        return [
            'total_receipts' => $receipts->count(),
            'successful_payments' => $successful->count(),
            'pending_payments' => $pending->count(),
            'failed_payments' => $failed->count(),
            'total_amount_paid' => $successful->sum('amount'),
        ];
    }
}