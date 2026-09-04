<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'amount_per_listen',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_listen' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Resolve the reward amount (in naira) for a given ISO country code,
     * falling back to the configured default when the country has no
     * active rate configured.
     */
    public static function amountFor(?string $countryCode): int
    {
        if ($countryCode) {
            $reward = static::where('country_code', strtoupper($countryCode))
                ->where('is_active', true)
                ->first();

            if ($reward) {
                return (int) $reward->amount_per_listen;
            }
        }

        return (int) (AppSetting::get('reward_per_listen_default', 100) ?? 100);
    }
}