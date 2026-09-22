<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            HeroSeeder::class,
            PartnerSeeder::class,
            CounterSeeder::class,
            EventSeeder::class,
            SuccessStorySeeder::class,
            TestimonialSeeder::class,
            FaqSeeder::class,
            BlogSeeder::class,
            ReelSeeder::class,
            AboutSectionSeeder::class,
            CourseModuleSeeder::class,
            PlacementSectionSeeder::class,
        ]);
    }
}
