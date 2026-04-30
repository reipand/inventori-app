<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_date',
        'subtotal',
        'total_discount',
        'total',
        'payment_method',
        'amount_paid',
        'change_amount',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'         => 'decimal:2',
            'total_discount'   => 'decimal:2',
            'total'            => 'decimal:2',
            'amount_paid'      => 'decimal:2',
            'change_amount'    => 'decimal:2',
            'transaction_date' => 'date',
            'created_at'       => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
