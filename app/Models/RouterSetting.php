<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouterSetting extends Model
{
    /** @use HasFactory<\Database\Factories\RouterSettingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'router_ip',
        'router_port',
        'router_username',
        'router_password',
        'hotspot_interface',
        'is_connected',
        'last_synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'router_password' => 'encrypted',
            'is_connected' => 'boolean',
            'last_synced_at' => 'datetime',
            'router_port' => 'integer',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->first();
    }

    public static function resolve(): self
    {
        return static::query()->first() ?? new static([
            'router_port' => 8728,
            'hotspot_interface' => 'hotspot',
            'is_connected' => false,
        ]);
    }

    public function isConfigured(): bool
    {
        return filled($this->router_ip)
            && filled($this->router_username)
            && filled($this->router_password);
    }
}
