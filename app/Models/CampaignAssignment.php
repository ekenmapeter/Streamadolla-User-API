<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAssignment extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_LISTENING = 'listening';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'campaign_id',
        'listener_id',
        'status',
        'assigned_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromoCampaign::class);
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'listener_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ASSIGNED, self::STATUS_LISTENING]);
    }
}