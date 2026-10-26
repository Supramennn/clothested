<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Fashion categories (ERD 2.3); slugs drive the storefront URLs.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Kaos', 'slug' => 'kaos', 'description' => 'Kaos polos dan graphic tee bahan cotton combed nyaman dipakai harian.'],
            ['name' => 'Kemeja', 'slug' => 'kemeja', 'description' => 'Kemeja kasual dan flanel untuk gaya kerja maupun santai.'],
            ['name' => 'Jaket', 'slug' => 'jaket', 'description' => 'Jaket denim, bomber, dan hoodie untuk semua musim.'],
            ['name' => 'Celana', 'slug' => 'celana', 'description' => 'Chino, jogger, dan celana pendek dengan potongan modern.'],
            ['name' => 'Dress', 'slug' => 'dress', 'description' => 'Dress midi dan mini dengan bahan flowy.'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
