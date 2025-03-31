<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $politics = Tag::create([
            'name' => 'Politik',
            'color' => '#EE82EE'
        ]);

        $sports = Tag::create([
            'name' => 'Sport',
            'color' => '#F5FF00'
        ]);

        Tag::create([
            'name' => 'Links',
            'super_tag' => $politics->id,
            'color' => '#FF0000',
        ]);

        Tag::create([
            'name' => 'Fußball',
            'super_tag' => $sports->id,
            'color' => '#00FF00',
        ]);
    }
}
