<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_ngn',
        'listen_target',
        'review_target',
        'margin_pct',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_ngn' => 'integer',
            'listen_target' => 'integer',
            'review_target' => 'integer',
            'margin_pct' => 'float',
            'is_active' => 'boolean',
        ];
    }
}