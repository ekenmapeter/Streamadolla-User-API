<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'platform', 'is_active',
        'channel_url', 'interstitial_every', 'interstitial_media_url', 'interstitial_duration_seconds',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tracks()
    {
        return $this->hasMany(CampaignTrack::class)->orderBy('position_order');
    }

    public function assignments()
    {
        return $this->hasMany(DeviceAssignment::class);
    }
}
