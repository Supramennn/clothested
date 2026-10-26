<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'sku', 'size', 'color', 'additional_price', 'stock'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'additional_price' => 'decimal:2',
        ];
    }

    /**
     * The product this SKU belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Cart lines pointing to this SKU.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Purchase history referencing this SKU.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Label shown on product pages and order items, e.g. "Navy / L".
     */
    public function label(): string
    {
        return $this->color.' / '.$this->size;
    }

    /**
     * FR-19: the purchasable amount of this SKU, base price plus its surcharge.
     */
    public function finalPrice(): float
    {
        return (float) $this->product->base_price + (float) $this->additional_price;
    }

    /**
     * Whether the SKU can serve the requested quantity (stock never goes negative).
     */
    public function isStockAvailable(int $quantity): bool
    {
        return $quantity >= 1 && $quantity <= $this->stock;
    }
}
