<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    /**
     * Demo fashion catalog: products, SKUs, and generated placeholder
     * artwork on the public disk so storefront pages have real images.
     */
    public function run(): void
    {
        $catalog = [
            [
                'name' => 'Kaos Cotton Combed Navy',
                'slug' => 'kaos-cotton-combed-navy',
                'category' => 'kaos',
                'base_price' => 129000,
                'is_featured' => true,
                'is_active' => true,
                'colors' => ['Navy' => 0, 'Krem' => 10000],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Kaos Graphic Rise Tee',
                'slug' => 'kaos-graphic-rise-tee',
                'category' => 'kaos',
                'base_price' => 149000,
                'is_featured' => true,
                'is_active' => true,
                'colors' => ['Hitam' => 0, 'Putih' => 5000],
                'sizes' => ['M', 'L', 'XL'],
            ],
            [
                'name' => 'Kemeja Flanel Woodland',
                'slug' => 'kemeja-flanel-woodland',
                'category' => 'kemeja',
                'base_price' => 199000,
                'is_featured' => false,
                'is_active' => true,
                'colors' => ['Merah Kotak' => 0, 'Hijau Kotak' => 15000],
                'sizes' => ['S', 'M', 'L'],
            ],
            [
                'name' => 'Jaket Denim Classic Indigo',
                'slug' => 'jaket-denim-classic-indigo',
                'category' => 'jaket',
                'base_price' => 349000,
                'is_featured' => true,
                'is_active' => true,
                'colors' => ['Indigo' => 0, 'Langit' => 20000],
                'sizes' => ['S', 'M', 'L', 'XL'],
            ],
            [
                'name' => 'Jaket Bomber Reversible',
                'slug' => 'jaket-bomber-reversible',
                'category' => 'jaket',
                'base_price' => 299000,
                'is_featured' => false,
                'is_active' => true,
                'colors' => ['Abu' => 0],
                'sizes' => ['M', 'L', 'XL'],
            ],
            [
                'name' => 'Celana Chino Slim Fit',
                'slug' => 'celana-chino-slim-fit',
                'category' => 'celana',
                'base_price' => 219000,
                'is_featured' => false,
                'is_active' => true,
                'colors' => ['Krem Sand' => 0, 'Navy' => 10000],
                'sizes' => ['S', 'M', 'L'],
            ],
            [
                'name' => 'Dress Midi Tencel Flow',
                'slug' => 'dress-midi-tencel-flow',
                'category' => 'dress',
                'base_price' => 259000,
                'is_featured' => true,
                'is_active' => true,
                'colors' => ['Dusty Rose' => 0, 'Daun Muda' => 15000],
                'sizes' => ['S', 'M', 'L'],
            ],
            [
                'name' => 'Kemeja Linen Panjang Summer',
                'slug' => 'kemeja-linen-panjang-summer',
                'category' => 'kemeja',
                'base_price' => 179000,
                'is_featured' => false,
                'is_active' => false,
                'colors' => ['Putih' => 0],
                'sizes' => ['M', 'L', 'XL'],
            ],
        ];

        foreach ($catalog as $entry) {
            $category = Category::query()->where('slug', $entry['category'])->firstOrFail();

            $product = Product::query()->updateOrCreate(
                ['slug' => $entry['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $entry['name'],
                    'description' => 'Produk demo ClothesTed. Potongan regular, kualitas jahitan rapi, cocok untuk pemakaian sehari-hari.',
                    'base_price' => $entry['base_price'],
                    'is_active' => $entry['is_active'],
                    'is_featured' => $entry['is_featured'],
                ]
            );

            $this->replaceVariants($product, $entry);
            $this->replaceImages($product);
        }
    }

    /**
     * Rebuild the SKU grid of the product.
     *
     * @param  array{colors: array<string, int>, sizes: list<string>}  $entry
     */
    private function replaceVariants(Product $product, array $entry): void
    {
        $product->variants()->delete();

        foreach ($entry['colors'] as $color => $additionalPrice) {
            foreach ($entry['sizes'] as $size) {
                $product->variants()->create([
                    'sku' => $this->buildSku($entry['slug'], $color, $size),
                    'size' => $size,
                    'color' => $color,
                    'additional_price' => $additionalPrice,
                    'stock' => fake()->numberBetween(4, 32),
                ]);
            }
        }
    }

    /**
     * Rebuild the gallery rows plus their placeholder files.
     */
    private function replaceImages(Product $product): void
    {
        $product->images()->each(function ($image): void {
            if (str_starts_with($image->image_path, 'products/')) {
                Storage::disk('public')->delete($image->image_path);
            }
        });

        $product->images()->delete();

        foreach (['4a90d9', '5fb0d9', '7cc0e8'] as $index => $_tint) {
            $path = 'products/'.$product->slug.'-'.($index + 1).'.svg';

            Storage::disk('public')->put($path, $this->placeholderSvg($product->name), [
                'visibility' => 'public',
            ]);

            $product->images()->create([
                'image_path' => $path,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Deterministic SKU: CT-<product>-<color>-<size>, e.g. CT-KAOSCOTTON-NAVY-S.
     */
    private function buildSku(string $slug, string $color, string $size): string
    {
        $productCode = strtoupper(str($slug)->replace('-', '')->take(9));
        $colorCode = strtoupper(str($color)->replace([' ', '/'], '')->take(5));

        return 'CT-'.$productCode.'-'.$colorCode.'-'.$size;
    }

    /**
     * Simple branded SVG placeholder; keeps the demo catalog image-safe.
     */
    private function placeholderSvg(string $title): string
    {
        $short = mb_substr($title, 0, 2);

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="600" height="750" viewBox="0 0 600 750">
              <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0" stop-color="#F0F9FF"/>
                  <stop offset="1" stop-color="#BAE6FD"/>
                </linearGradient>
              </defs>
              <rect width="600" height="750" fill="url(#bg)"/>
              <text x="300" y="360" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="120" font-weight="700" fill="#0C4A6E">{$short}</text>
              <text x="300" y="410" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="22" fill="#0369A1">ClothesTed</text>
            </svg>
            SVG;
    }
}
