<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Class 3.4: ProductDetail (variant picker, final price, add to cart, wishlist).
 */
#[Layout('layouts.app')]
class ProductDetail extends Component
{
    public string $slug = '';

    public ?string $color = null;

    public ?string $size = null;

    public ?string $addedMessage = null;

    public ?int $selectedImageId = null;

    /**
     * FR-22: inactive or missing slugs are simply not found, old links too.
     */
    public function mount(string $slug): void
    {
        $this->slug = $slug;

        $product = $this->product();

        $this->color = $product->variants->first()?->color;
        $this->size = $this->sizeOptions()[0] ?? null;
    }

    /**
     * The live product with its variants and gallery loaded; guests included.
     */
    #[Computed]
    public function product(): Product
    {
        return Product::query()
            ->active()
            ->with(['category', 'variants', 'images'])
            ->where('slug', $this->slug)
            ->firstOr(fn () => abort(404));
    }

    /**
     * Every SKU of the product, untouched for the picker.
     *
     * @return Collection<int, ProductVariant>
     */
    #[Computed]
    public function variants(): Collection
    {
        return $this->product()->variants;
    }

    /**
     * FR-18: colors are picked first, each unique among the product SKUs.
     *
     * @return list<string>
     */
    #[Computed]
    public function colorOptions(): array
    {
        return $this->variants->pluck('color')->unique()->values()->all();
    }

    /**
     * FR-18: sizes are picked second, restricted to the chosen color.
     *
     * @return list<string>
     */
    #[Computed]
    public function sizeOptions(): array
    {
        if ($this->color === null) {
            return [];
        }

        return $this->variants
            ->where('color', $this->color)
            ->pluck('size')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The SKU backing the current color and size picks.
     */
    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->variants->first(
            fn (ProductVariant $variant): bool => $variant->color === $this->color && $variant->size === $this->size
        );
    }

    /**
     * FR-19: base price plus the selected variant's surcharge.
     */
    #[Computed]
    public function finalPrice(): float
    {
        $variant = $this->selectedVariant();

        return $variant !== null
            ? $variant->finalPrice()
            : (float) $this->product()->base_price;
    }

    /**
     * The gallery holds only images; clicking a thumb re-seats the main shot.
     */
    public function selectImage(int $imageId): void
    {
        if ($this->product()->images->contains('id', $imageId)) {
            $this->selectedImageId = $imageId;
        }
    }

    /**
     * The image currently shown in the main frame.
     */
    #[Computed]
    public function displayedImage(): ?ProductImage
    {
        $images = $this->product()->images;

        return $images->firstWhere('id', $this->selectedImageId) ?? $images->first();
    }

    /**
     * FR-20: out of stock SKUs are not purchasable.
     */
    #[Computed]
    public function inStock(): bool
    {
        return ($this->selectedVariant()?->stock ?? 0) > 0;
    }

    /**
     * Color change re-seats the size pick onto a valid combination.
     */
    public function selectColor(string $color): void
    {
        if (! in_array($color, $this->colorOptions(), true)) {
            return;
        }

        $this->color = $color;

        $this->addedMessage = null;

        if (! in_array($this->size, $this->sizeOptions(), true)) {
            $this->size = $this->sizeOptions()[0] ?? null;
        }
    }

    /**
     * Size change only accepts combinations the catalog actually carries.
     */
    public function selectSize(string $size): void
    {
        if (in_array($size, $this->sizeOptions(), true)) {
            $this->size = $size;

            $this->addedMessage = null;
        }
    }

    /**
     * FR-20: add the selected SKU to the account cart; guests are shown the
     * login screen and sent back afterwards. Stock never goes negative.
     */
    public function addToCart(): void
    {
        $variant = $this->selectedVariant();

        if ($variant === null || $variant->stock < 1) {
            return;
        }

        $user = Auth::user();

        if ($user === null) {
            session()->put('url.intended', url()->full());

            $this->redirect(route('login'));

            return;
        }

        CartItem::mergeAdd($user->id, $variant);

        $this->addedMessage = 'Added to cart: '.$variant->label().'.';

        $this->dispatch('cart-updated');
    }

    /**
     * FR-21: the wishlist stores the product, not a SKU; repeated clicks
     * toggle the state.
     */
    public function toggleWishlist(): void
    {
        $user = Auth::user();

        if ($user === null) {
            session()->put('url.intended', url()->full());

            $this->redirect(route('login'));

            return;
        }

        $saved = Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $this->product()->id)
            ->first();

        if ($saved !== null) {
            $saved->delete();

            return;
        }

        Wishlist::query()->create([
            'user_id' => $user->id,
            'product_id' => $this->product()->id,
            'created_at' => now(),
        ]);
    }

    /**
     * FR-21: saved state shown to the signed-in customer.
     */
    #[Computed]
    public function inWishlist(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $this->product()->id)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.product-detail', ['product' => $this->product]);
    }
}
