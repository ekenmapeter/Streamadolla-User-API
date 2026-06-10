<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAssignment extends Model
{
    protected $fillable = [
        'device_id',
        'campaign_id',
        'campaign_track_id',
        'subset_start_index',
        'subset_end_index',
        'shuffled_index',
        'cycle_track_count',
        'is_interstitial',
        'platform',
        'media_url',
        'media_title',
        'status',
        'assigned_at',
        'started_at',
    ];

    protected $casts = [
        'assigned_at'    => 'datetime',
        'started_at'     => 'datetime',
        'is_interstitial' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignTrack()
    {
        return $this->belongsTo(CampaignTrack::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    /**
     * Active assignments (pending, playing, paused).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'playing', 'paused']);
    }

    /**
     * Assignments for a specific device.
     */
    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Filter by platform.
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Check if the assignment is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'playing', 'paused']);
    }

    /**
     * Friendly status label with emoji.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => '⏳ Pending',
            'playing'   => '▶️ Playing',
            'paused'    => '⏸️ Paused',
            'stopped'   => '⏹️ Stopped',
            'failed'    => '❌ Failed',
            'completed' => '✅ Completed',
            default     => $this->status,
        };
    }
}
