<?php

namespace Database\Seeders;

use App\Models\Hero;
use Illuminate\Database\Seeder;

/**
 * Seeds the single hero row from the home page's original hardcoded content
 * (kept on the model so the seeder and the read-time fallback never drift).
 */
class HeroSeeder extends Seeder
{
    public function run(): void
    {
        Hero::updateOrCreate(['id' => 1], Hero::defaults());
    }
}
