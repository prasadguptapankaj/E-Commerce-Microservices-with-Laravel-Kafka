<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Apple iPhone 15 Pro',
                'quantity' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'quantity' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'OnePlus 12R',
                'quantity' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
