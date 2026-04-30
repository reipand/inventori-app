<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'qty',
        'sell_price',
        'cogs',
        'discount_per_item',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'sell_price'       => 'decimal:2',
            'cogs'             => 'decimal:2',
            'discount_per_item' => 'decimal:2',
            'subtotal'         => 'decimal:2',
            'qty'              => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
