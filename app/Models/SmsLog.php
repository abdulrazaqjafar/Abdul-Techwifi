<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'phone',
        'message',
        'provider',
        'status',
        'response',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
