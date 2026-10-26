<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'midtrans_order_id', 'midtrans_transaction_id', 'payment_type', 'gross_amount', 'transaction_status', 'expiry_time', 'paid_at', 'raw_payload'])]
class Payment extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'expiry_time' => 'datetime',
            'paid_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    /**
     * The paid order; one payment per order (ERD 2.2, order_id UNIQUE).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
