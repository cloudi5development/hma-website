<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

/**
 * Seeds the four partners that were hardcoded in partials/partners.blade.php, so
 * the home and about pages render identically after the section becomes dynamic.
 * Idempotent: keyed on name via updateOrCreate.
 */
class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            ['name' => 'Amazon',    'logo' => 'assets/images/partners-section/amason.webp'],
            ['name' => 'Google',    'logo' => 'assets/images/partners-section/google.webp'],
            ['name' => 'Microsoft', 'logo' => 'assets/images/partners-section/microsoft.webp'],
            ['name' => 'Tech',      'logo' => 'assets/images/partners-section/tech.webp'],
        ];

        foreach ($partners as $i => $p) {
            Partner::updateOrCreate(
                ['name' => $p['name']],
                [
                    'logo'              => $p['logo'],
                    'sort_order'        => $i,
                    'is_active'         => true,
                    'show_home'         => true,
                    'show_about'        => true,
                    'show_testimonials' => false,
                ]
            );
        }
    }
}
