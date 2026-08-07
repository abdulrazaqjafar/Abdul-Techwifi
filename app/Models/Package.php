<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'duration_hours',
        'duration_days',
        'price',
        'profile_name',
        'description',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'duration_hours' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * How long the router should let a session run, in RouterOS notation.
     */
    public function getRouterUptimeLimit(): ?string
    {
        if ($this->duration_days > 0) {
            return $this->duration_days.'d';
        }

        if ($this->duration_hours > 0) {
            return $this->duration_hours.'h';
        }

        return null;
    }

    public function getDurationLabel(): string
    {
        if ($this->duration_days === 30) {
            return '1 month';
        }

        if ($this->duration_days === 14) {
            return '2 weeks';
        }

        if ($this->duration_days === 7) {
            return '1 week';
        }

        if ($this->duration_days === 1) {
            return '1 day';
        }

        if ($this->duration_days > 0) {
            return $this->duration_days.' days';
        }

        if ($this->duration_hours === 24) {
            return '1 day';
        }

        if ($this->duration_hours > 0) {
            return $this->duration_hours.' '.($this->duration_hours === 1 ? 'hour' : 'hours');
        }

        return 'Unlimited';
    }

    public function getDailyRate(): ?float
    {
        $days = $this->duration_days;

        if ($days === 30) {
            $days = 30;
        }

        if ($days <= 0 && $this->duration_hours === 24) {
            $days = 1;
        }

        if ($days <= 0) {
            return null;
        }

        return round((float) $this->price / $days, 0);
    }
}
