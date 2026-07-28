<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-page SEO — one row per frontend page, keyed by its route name. What the
 * admin sets here wins over the meta hard-coded in the page's Blade file, which
 * in turn falls back to the site-wide defaults in Settings → SEO Defaults.
 *
 * The default rows are inserted here rather than in a seeder so a live deploy
 * only ever needs `php artisan migrate --force`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // frontend route name, e.g. "about-us"
            $table->string('label');                  // human name shown in the admin list
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('meta_robots')->default('index, follow');
            $table->string('og_image')->nullable();   // social share image, path relative to /public
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        $now = now();

        DB::table('seo_pages')->insert(array_map(fn ($row) => $row + [
            'meta_robots' => 'index, follow',
            'is_active'   => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ], [
            [
                'key'              => 'index',
                'label'            => 'Home',
                'title'            => 'Hire Minds Academy — Learn, Practice, Get Hired',
                'meta_description' => 'Hire Minds Academy turns ambition into a career. Master in-demand tech skills through hands-on projects, real practice and mentorship — from your first line of code to your first job offer.',
                'meta_keywords'    => 'hire minds academy, tech training, placement training, learn to code',
            ],
            [
                'key'              => 'about-us',
                'label'            => 'About Us',
                'title'            => 'About Us — Hire Minds Academy',
                'meta_description' => 'Hire Minds Academy empowers talent through industry-ready learning — practical, career-focused training that prepares learners for today\'s competitive job market.',
                'meta_keywords'    => 'about hire minds academy, industry ready training',
            ],
            [
                'key'              => 'courses',
                'label'            => 'Courses',
                'title'            => 'Courses — Hire Minds Academy',
                'meta_description' => 'Explore career-focused technical training programs at Hire Minds Academy — built to help you gain practical skills, confidence, and an edge in a competitive job market.',
                'meta_keywords'    => 'technical courses, software training, professional courses',
            ],
            [
                'key'              => 'testimonials',
                'label'            => 'Testimonials',
                'title'            => 'Testimonials — Hire Minds Academy',
                'meta_description' => 'Real stories from Hire Minds Academy learners — how they gained industry-ready skills, secured opportunities, and built successful careers.',
                'meta_keywords'    => 'student reviews, placement stories, testimonials',
            ],
            [
                'key'              => 'blog',
                'label'            => 'Blog',
                'title'            => 'Blog — Hire Minds Academy',
                'meta_description' => 'Explore articles, career advice, interview tips, and industry updates from Hire Minds Academy — written to keep you ahead in a competitive job market.',
                'meta_keywords'    => 'career advice, interview tips, tech blog',
            ],
            [
                'key'              => 'contact-us',
                'label'            => 'Contact Us',
                'title'            => 'Contact Us — Hire Minds Academy',
                'meta_description' => 'Talk to Hire Minds Academy. Visit our Chennai or Coimbatore branch, call us, or send an enquiry — our team replies within 24 hours.',
                'meta_keywords'    => 'contact hire minds academy, enquiry, chennai, coimbatore',
            ],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
