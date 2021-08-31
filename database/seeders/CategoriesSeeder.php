<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $category = new Category();
        $category->name = 'Homestay';
        $category->save();

        $category = new Category();
        $category->name = 'Nhà riêng';
        $category->save();

        $category = new Category();
        $category->name = 'Biệt thự';
        $category->save();

        $category = new Category();
        $category->name = 'Resort';
        $category->save();

        $category = new Category();
        $category->name = 'Villa';
        $category->save();

        $category = new Category();
        $category->name = 'Penthouse';
        $category->save();
    }
}
