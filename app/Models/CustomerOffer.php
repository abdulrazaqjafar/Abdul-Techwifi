<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerOffer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_EXPIRED = 'expired';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'offer_code',
        'discount_percent',
        'message',
        'status',
        'month_key',
        'valid_from',
        'valid_until',
        'sms_sent_at',
        'redeemed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'sms_sent_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_SENT], true)
            && now()->between($this->valid_from, $this->valid_until);
    }
}
