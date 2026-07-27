<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Reels are short uploaded video clips (Sections → Our Journey) that autoplay
 * in the cards. There are no shipped sample videos, so there is nothing to
 * seed — the "Our Journey" / "Career Success" slider stays hidden until the
 * first reel is uploaded from the admin panel.
 */
class ReelSeeder extends Seeder
{
    public function run(): void
    {
        // Intentionally empty — add reels from the admin panel.
    }
}
