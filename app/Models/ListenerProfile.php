<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListenerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'genre_prefs',
        'trust_level',
        'streak',
        'total_earned',
    ];

    protected function casts(): array
    {
        return [
            'genre_prefs' => 'array',
            'trust_level' => 'integer',
            'streak' => 'integer',
            'total_earned' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ListenSession::class, 'listener_id', 'user_id');
    }

    public function addEarnings(int $amount): void
    {
        $this->increment('total_earned', $amount);
        $this->increment('streak');
        $this->save();
    }
}