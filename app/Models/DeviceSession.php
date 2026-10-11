<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_REWARDED = 'rewarded';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_FRAUD = 'fraud';

    protected $fillable = [
        'vendor_id',
        'device_id',
        'device_assignment_id',
        'min_duration_seconds',
        'elapsed_seconds',
        'checkpoints',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'checkpoints' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeviceAssignment::class, 'device_assignment_id');
    }

    public function hasMetDuration(): bool
    {
        return $this->elapsed_seconds >= $this->min_duration_seconds;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}