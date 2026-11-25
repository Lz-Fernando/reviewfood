<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            'Pizzaria',
            'Hamburgueria',
            'Japonesa',
            'Brasileira',
            'Italiana',
            'Mexicana',
            'Churrascaria',
            'Cafeteria',
            'Confeitaria',
            'Padaria',
            'Bar/Pub',
            'Sorveteria',
            'Frutos do mar',
            'Vegetariana/Vegana',
            'Outros',
        ];

        foreach ($defaultCategories as $category) {
            Category::query()->firstOrCreate([
                'name' => $category,
                'type' => CategoryType::DEFAULT,
                'group_id' => null,
            ]);
        }
    }
}
