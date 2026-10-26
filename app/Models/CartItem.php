<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'product_variant_id', 'quantity'])]
class CartItem extends Model
{
    /**
     * The customer owning this cart line.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The SKU held in this cart line.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * FR-26: the same SKU merges into one row with an increased quantity.
     * FR-27: quantity never leaves 1..30 or the live stock, so call this only
     * for a SKU with stock >= 1. Returns the affected row.
     */
    public static function mergeAdd(int $userId, ProductVariant $variant): CartItem
    {
        $quantityCap = min(30, $variant->stock);

        $row = static::query()
            ->where('user_id', $userId)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($row === null) {
            return static::query()->create([
                'user_id' => $userId,
                'product_variant_id' => $variant->id,
                'quantity' => min(1, $quantityCap),
            ]);
        }

        $row->update(['quantity' => min($row->quantity + 1, $quantityCap)]);

        return $row->refresh();
    }
}
