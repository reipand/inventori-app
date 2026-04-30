<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'qty',
        'price_input',
        'price_mode',
        'discount_item_type',
        'discount_item_value',
        'price_per_unit_final',
        'global_discount_portion',
        'cogs_per_unit',
        'subtotal_final',
    ];

    protected function casts(): array
    {
        return [
            'price_input'             => 'decimal:2',
            'discount_item_value'     => 'decimal:2',
            'price_per_unit_final'    => 'decimal:2',
            'global_discount_portion' => 'decimal:2',
            'cogs_per_unit'           => 'decimal:2',
            'subtotal_final'          => 'decimal:2',
            'qty'                     => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
