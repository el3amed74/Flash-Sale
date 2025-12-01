<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'stock',
        'reserved',
        'sold',
        
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'reserved' => 'integer',
        'sold' => 'integer',
        
    ];

    // Relations
    public function holds()
    {
        return $this->hasMany(Hold::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Available computed attribute = stock - reserved - sold
     */
    protected function available(): Attribute
    {
        return Attribute::get(function () {
            return max(0, $this->stock - $this->reserved - $this->sold);
        });
    }
}
