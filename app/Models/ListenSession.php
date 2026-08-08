<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ListenSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_REWARDED = 'rewarded';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_FRAUD = 'fraud';

    protected $fillable = [
        'listener_id',
        'assignment_id',
        'session_token',
        'min_duration_seconds',
        'elapsed_seconds',
        'checkpoints',
        'foreground',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'checkpoints' => 'array',
            'foreground' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ListenSession $session) {
            $session->session_token = Str::random(64);
        });
    }

    public function listener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'listener_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CampaignAssignment::class);
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