<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $table = 'payment_attempts';

    protected $fillable = [
        'subscription_id',
        'status',
        'retry_count',
        'next_retry_at',
        'response_payload'
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'response_payload' => 'array'
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}