<?php

namespace Database\Seeders;

use App\Models\SuccessStory;
use Illuminate\Database\Seeder;

/**
 * Seeds the four cards verbatim from the old hardcoded array in
 * frontend/index.blade.php, so the rendered grid stays identical.
 */
class SuccessStorySeeder extends Seeder
{
    public function run(): void
    {
        $stories = [
            ['image' => 'assets/images/success-story/person-1.webp', 'salary' => '9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'olive'],
            ['image' => 'assets/images/success-story/person-2.webp', 'salary' => '9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'teal'],
            ['image' => 'assets/images/success-story/person-3.webp', 'salary' => '9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'green'],
            ['image' => 'assets/images/success-story/person-4.webp', 'salary' => '9.0', 'name' => 'Aayushman Pravin', 'role' => 'Software Developer', 'tone' => 'violet'],
        ];

        foreach ($stories as $i => $s) {
            SuccessStory::updateOrCreate(
                ['image' => $s['image']],
                [
                    'name'       => $s['name'],
                    'role'       => $s['role'],
                    'salary'     => $s['salary'],
                    'tone'       => $s['tone'],
                    'sort_order' => $i,
                    'is_active'  => true,
                    'show_home'  => true,
                ]
            );
        }
    }
}
