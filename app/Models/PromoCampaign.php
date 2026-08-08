<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PromoCampaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'promo_campaigns';

    protected $fillable = [
        'artist_id',
        'package_id',
        'title',
        'track_url',
        'platform',
        'genres',
        'reward_per_review',
        'listen_target_total',
        'review_target',
        'status',
        'starts_at',
        'ends_at',
        'funded_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'genres' => 'array',
            'reward_per_review' => 'integer',
            'listen_target_total' => 'integer',
            'review_target' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'funded_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function assignments(): HasMany
    {
        return $this->hasMany(CampaignAssignment::class, 'campaign_id');
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(ListenSession::class, CampaignAssignment::class, 'campaign_id', 'assignment_id', 'id', 'id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOwnedBy(Builder $query, int $artistId): Builder
    {
        return $query->where('artist_id', $artistId);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function rewardedSessionCount(): int
    {
        return $this->sessions()
            ->where('listen_sessions.status', ListenSession::STATUS_REWARDED)
            ->count();
    }
}