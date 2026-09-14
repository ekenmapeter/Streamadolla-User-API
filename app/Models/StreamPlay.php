<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamPlay extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_REWARDED = 'rewarded';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'rental_id',
        'device_assignment_id',
        'track_id',
        'platform',
        'media_url',
        'reward_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(DeviceRental::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeviceAssignment::class, 'device_assignment_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function scopeRewarded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REWARDED);
    }

    public static function existsForAssignment(int $assignmentId): bool
    {
        return static::where('device_assignment_id', $assignmentId)->exists();
    }
}