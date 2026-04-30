<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_number',
        'supplier_name',
        'invoice_date',
        'discount_global_type',
        'discount_global_value',
        'total_before_discount',
        'total_discount',
        'total_final',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_global_value'  => 'decimal:2',
            'total_before_discount'  => 'decimal:2',
            'total_discount'         => 'decimal:2',
            'total_final'            => 'decimal:2',
            'invoice_date'           => 'date',
            'created_at'             => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
