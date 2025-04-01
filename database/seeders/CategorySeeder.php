<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::insert([
            [
                'name' => 'Протеиновые батончики и печенье',
                'slug' => 'proteinovye-batonchiki-i-pechenye'
            ],
            [
                'name' => 'Протеины',
                'slug' => 'proteiny'
            ],
            [
                'name' => 'Аминокислоты',
                'slug' => 'aminokisloty'
            ],
            [
                'name' => 'Витамины и минералы',
                'slug' => 'vitaminy-i-mineraly'
            ],
            [
                'name' => 'Изотоники',
                'slug' => 'izotoniki'
            ],
        ]);
    }
}
