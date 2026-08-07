<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'voucher_id',
        'mikrotik_username',
        'ip_address',
        'mac_address',
        'is_online',
        'rate_limit',
        'bytes_in',
        'bytes_out',
        'uptime_seconds',
        'quality_score',
        'quality_label',
        'notes',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
