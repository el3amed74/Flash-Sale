<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'hold_id',
        'product_id',
        'qty',
        'total_price',
        'status',
    ];

    protected $casts = [
        'qty' => 'integer',
        'total_price' => 'decimal:2'
    ];

    // Relations
    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // helper checks
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
