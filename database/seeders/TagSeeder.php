<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tags')->insert([
            [
                'name' => 'Links',
                'super_tag' => null,
                'color' => '#FF0000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fußball',
                'super_tag' => null,
                'color' => '#00FF00',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
