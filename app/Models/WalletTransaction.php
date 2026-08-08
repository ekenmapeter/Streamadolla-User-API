<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    public const TYPE_REWARD = 'reward';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_PAYOUT = 'payout';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CREDITED = 'credited';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'reference',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function nextReference(): string
    {
        return 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
    }
}