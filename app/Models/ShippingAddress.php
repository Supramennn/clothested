<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'recipient_name', 'phone', 'address_line', 'city', 'postal_code', 'waybill_number'])]
class ShippingAddress extends Model
{
    public $timestamps = false;

    /**
     * The courier order this address belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
