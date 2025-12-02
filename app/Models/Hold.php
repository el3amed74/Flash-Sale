<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hold extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'product_id',
        'qty',
        'status',
        'expires_at',
        'used_at',
        'idempotency_key',
        'meta',
    ];

    protected $casts = [
        'qty' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'meta' => 'array',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'hold_id');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
