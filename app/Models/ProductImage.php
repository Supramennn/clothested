<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['product_id', 'image_path', 'sort_order'])]
class ProductImage extends Model
{
    /**
     * The product this image belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Public URL served through the storage symlink.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
