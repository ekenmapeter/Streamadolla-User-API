<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'device_id',
        'fcm_token',
        'proxy_url',
        'status',
        'last_seen',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen' => 'datetime'
    ];

    // ── Relationships ────────────────────────────────────────────────────

    /**
     * All assignments for this device.
     */
    public function assignments()
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    /**
     * Active assignments (pending, playing, paused).
     */
    public function activeAssignments()
    {
        return $this->hasMany(DeviceAssignment::class)
                    ->whereIn('status', ['pending', 'playing', 'paused']);
    }

    /**
     * The most recent active assignment.
     */
    public function currentAssignment()
    {
        return $this->hasOne(DeviceAssignment::class)
                    ->whereIn('status', ['pending', 'playing', 'paused'])
                    ->latest('assigned_at');
    }

    /**
     * Device logs.
     */
    public function logs()
    {
        return $this->hasMany(DeviceLog::class, 'device_id', 'device_id');
    }
}
