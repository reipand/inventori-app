<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'unit',
        'buy_price',
        'sell_price',
        'min_stock',
        'current_stock',
    ];

    protected function casts(): array
    {
        return [
            'buy_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'min_stock' => 'integer',
            'current_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock === 0) {
            return 'habis';
        }
        if ($this->current_stock <= $this->min_stock) {
            return 'rendah';
        }
        return 'normal';
    }
}
