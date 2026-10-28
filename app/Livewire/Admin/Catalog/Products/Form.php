<?php

namespace App\Livewire\Admin\Catalog\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Product form · ClothesTed')]
class Form extends Component
{
    public ?int $productId = null;

    public ?int $categoryId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public string $basePrice = '';

    public bool $isActive = false;

    public bool $isFeatured = false;

    /** @var array<int, array<string, int|string|null>> */
    public array $variants = [];

    /** @var list<int> */
    public array $removedVariantIds = [];

    public function mount(?Product $product = null): void
    {
        if ($product === null) {
            return;
        }

        $this->productId = $product->id;
        $this->categoryId = $product->category_id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->description = $product->description ?? '';
        $this->basePrice = (string) $product->base_price;
        $this->isActive = $product->is_active;
        $this->isFeatured = $product->is_featured;
        $this->variants = $product->variants()
            ->orderBy('id')
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'size' => $variant->size,
                'color' => $variant->color,
                'additional_price' => (string) $variant->additional_price,
                'stock' => $variant->stock,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->ordered()->get();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($this->productId),
            ],
            'description' => ['nullable', 'string'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
            'isFeatured' => ['boolean'],
            'variants' => ['array'],
            'removedVariantIds' => ['array'],
            'removedVariantIds.*' => ['integer', 'distinct'],
        ];
    }

    public function addVariant(): void
    {
        $this->variants[] = [
            'id' => null,
            'sku' => '',
            'size' => 'M',
            'color' => '',
            'additional_price' => '0',
            'stock' => 0,
        ];
    }

    public function removeVariant(int $index): void
    {
        $variant = $this->variants[$index] ?? null;

        if ($variant === null) {
            return;
        }

        if (isset($variant['id'])) {
            $this->removedVariantIds[] = (int) $variant['id'];
        }

        array_splice($this->variants, $index, 1);
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $variants = $this->validatedVariants();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($this->isActive && $variants === []) {
            $this->addError('variants', 'Add at least one SKU before publishing this product.');

            return;
        }

        DB::transaction(function () use ($validated, $variants): void {
            $product = $this->productId === null
                ? null
                : Product::query()->lockForUpdate()->findOrFail($this->productId);

            if ($product !== null) {
                $this->syncVariants($product, $variants);
            }

            $attributes = [
                'category_id' => $validated['categoryId'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?: null,
                'base_price' => $validated['basePrice'],
                'is_active' => $validated['isActive'],
                'is_featured' => $validated['isFeatured'],
            ];

            if ($product === null) {
                $product = Product::query()->create($attributes);
            } else {
                $product->update($attributes);
            }

            foreach ($variants as $variant) {
                if ($variant['id'] === null) {
                    $product->variants()->create([
                        'sku' => $variant['sku'],
                        'size' => $variant['size'],
                        'color' => $variant['color'],
                        'additional_price' => $variant['additional_price'],
                        'stock' => $variant['stock'],
                    ]);

                    continue;
                }

                $existing = $product->variants()
                    ->whereKey($variant['id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $existing->update([
                    'sku' => $variant['sku'],
                    'size' => $variant['size'],
                    'color' => $variant['color'],
                    'additional_price' => $variant['additional_price'],
                ]);
            }
        });

        session()->flash('status', 'Product saved.');
        $this->redirect(route('admin.products.index'));
    }

    /**
     * @return list<array{id: int|null, sku: string, size: string, color: string, additional_price: string|int|float, stock?: int|string}>
     */
    private function validatedVariants(): array
    {
        $this->resetValidation();
        $rows = [];
        $seenSkus = [];
        $seenIds = [];
        $allowedSizes = ['S', 'M', 'L', 'XL'];

        foreach ($this->variants as $index => $row) {
            if (! is_array($row)) {
                $this->addError('variants.'.$index, 'Each SKU must be a complete row.');

                continue;
            }

            $variantId = isset($row['id']) ? (int) $row['id'] : null;
            $sku = is_string($row['sku'] ?? null) ? trim($row['sku']) : '';
            $color = is_string($row['color'] ?? null) ? trim($row['color']) : '';

            if ($variantId === null && $sku === '' && $color === '') {
                continue;
            }

            if ($variantId !== null && isset($seenIds[$variantId])) {
                $this->addError('variants.'.$index.'.id', 'Each existing SKU can appear only once.');

                continue;
            }

            $row['sku'] = $sku;
            $row['color'] = $color;

            $validator = Validator::make($row, [
                'id' => [
                    'nullable',
                    'integer',
                    'distinct',
                    Rule::exists('product_variants', 'id')->where('product_id', $this->productId ?? 0),
                ],
                'sku' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('product_variants', 'sku')->ignore($variantId),
                ],
                'size' => ['required', Rule::in($allowedSizes)],
                'color' => ['required', 'string', 'max:255'],
                'additional_price' => ['required', 'numeric', 'min:0'],
                'stock' => $variantId === null ? ['required', 'integer', 'min:0'] : ['nullable'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError('variants.'.$index.'.'.$field, $message);
                    }
                }

                continue;
            }

            $validated = $validator->validated();
            if (isset($seenSkus[$validated['sku']])) {
                $this->addError('variants.'.$index.'.sku', 'Each SKU must be unique.');

                continue;
            }

            $seenSkus[$validated['sku']] = true;
            if ($variantId !== null) {
                $seenIds[$variantId] = true;
            }

            $rows[] = [
                'id' => $variantId,
                'sku' => $validated['sku'],
                'size' => $validated['size'],
                'color' => trim($validated['color']),
                'additional_price' => $validated['additional_price'],
                ...($variantId === null ? ['stock' => (int) $validated['stock']] : []),
            ];
        }

        if ($this->productId !== null) {
            $currentIds = ProductVariant::query()
                ->where('product_id', $this->productId)
                ->whereNotIn('id', $this->removedVariantIds)
                ->pluck('id')
                ->map(fn (int $id): int => $id)
                ->all();
            $submittedIds = array_values(array_filter(array_column($rows, 'id')));

            if (
                array_diff($currentIds, $submittedIds) !== []
                || array_intersect($this->removedVariantIds, $submittedIds) !== []
            ) {
                $this->addError('variants', 'Keep each existing SKU in the form or remove it explicitly.');
            }
        }

        if ($this->removedVariantIds !== [] && $this->productId !== null) {
            $usedVariants = ProductVariant::query()
                ->where('product_id', $this->productId)
                ->whereIn('id', $this->removedVariantIds)
                ->where(fn ($query) => $query->whereHas('orderItems')->orWhereHas('cartItems'))
                ->exists();

            if ($usedVariants) {
                $this->addError('variants', 'SKUs in an order or customer cart cannot be removed.');
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{id: int|null, sku: string, size: string, color: string, additional_price: string|int|float, stock?: int|string}>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $removed = array_values(array_unique(array_map('intval', $this->removedVariantIds)));

        if ($removed !== []) {
            $variantsToRemove = $product->variants()
                ->whereIn('id', $removed)
                ->lockForUpdate()
                ->get();

            if ($variantsToRemove->count() !== count($removed)) {
                throw ValidationException::withMessages([
                    'variants' => 'One or more SKUs no longer belong to this product.',
                ]);
            }

            foreach ($variantsToRemove as $variant) {
                if ($variant->orderItems()->exists() || $variant->cartItems()->exists()) {
                    throw ValidationException::withMessages([
                        'variants' => 'SKUs in an order or customer cart cannot be removed.',
                    ]);
                }

                $variant->delete();
            }
        }

    }

    public function render(): View
    {
        return view('livewire.admin.catalog.products.form');
    }
}
