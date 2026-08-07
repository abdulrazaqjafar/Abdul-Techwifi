<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'notes',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(CustomerOffer::class);
    }

    public function serviceSnapshots(): HasMany
    {
        return $this->hasMany(CustomerServiceSnapshot::class);
    }

    public function latestServiceSnapshot(): HasMany
    {
        return $this->hasMany(CustomerServiceSnapshot::class)->latest('recorded_at');
    }

    public function activeVoucher(): ?Voucher
    {
        return $this->vouchers()
            ->where('status', Voucher::STATUS_USED)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereNotNull('mikrotik_username')
            ->latest('activated_at')
            ->first();
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
