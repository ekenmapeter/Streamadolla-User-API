<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Track extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_LIVE = 'live';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'artist_id',
        'package_id',
        'title',
        'artist_name',
        'platform',
        'media_url',
        'genre',
        'duration_seconds',
        'status',
        'payment_reference',
        'amount_paid_ngn',
        'stream_budget',
        'streams_served',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'amount_paid_ngn' => 'integer',
            'stream_budget' => 'integer',
            'streams_served' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(CampaignPackage::class);
    }

    public function streamPlays(): HasMany
    {
        return $this->hasMany(StreamPlay::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LIVE);
    }

    public function scopeOwnedBy(Builder $query, int $artistId): Builder
    {
        return $query->where('artist_id', $artistId);
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function hasBudgetRemaining(): bool
    {
        return $this->streams_served < $this->stream_budget;
    }
}