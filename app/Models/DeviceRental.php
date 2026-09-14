<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRental extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_TERMINATED = 'terminated';

    protected $fillable = [
        'user_id',
        'device_id',
        'status',
        'rate_per_stream',
        'started_at',
        'terminated_at',
        'total_streams',
        'total_earned',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_stream' => 'integer',
            'started_at' => 'datetime',
            'terminated_at' => 'datetime',
            'total_streams' => 'integer',
            'total_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function streamPlays()
    {
        return $this->hasMany(StreamPlay::class, 'rental_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}