<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Counter;
use App\Models\Course;
use App\Models\Department;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Hero;
use App\Models\Partner;
use App\Models\Reel;
use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\SuccessStory;
use App\Models\Testimonial;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->composeFrontendSections();

        // Admin pagination (->links()) uses our minimal .pagination markup —
        // page buttons only, no "Showing X to Y of Z results" text. admin.css
        // themes it. Frontend uses its own custom pagination, so it is unaffected.
        Paginator::defaultView('pagination.admin');

        // Admin topbar bell — recent notifications + unread count on every page.
        View::composer('backend.template.layouts.header', function ($view) {
            $view->with('adminNotifications', AdminNotification::latest()->take(8)->get());
            $view->with('adminNotifUnread', AdminNotification::where('is_read', false)->count());
        });
    }

    /**
     * Feed the shared frontend section partials from the database without the
     * pages/controllers having to pass anything. Each partial keeps its exact
     * markup — only the data source moves from a hardcoded array to Eloquent.
     */
    protected function composeFrontendSections(): void
    {
        // Per-page SEO for the document head. Resolved from the current route
        // name, so no page or controller has to pass anything.
        View::composer([
            'frontend.layouts.template-base',
            'frontend.layouts.meta-tags',
            'frontend.layouts.seo-content',
        ], function ($view) {
            $view->with('seo', SeoPage::forRoute(Route::currentRouteName()));
        });

        // Contact details (Settings → Contact) for every surface that shows them:
        // the footer, the shared enquiry form and the contact page's branch map.
        View::composer([
            'frontend.layouts.footer',
            'frontend.partials.contact-form',
            'frontend.contact-us',
        ], function ($view) {
            $view->with('contact', Setting::contactDetails());
        });

        // Social profile links (Settings → Social Media) for the footer icons.
        View::composer('frontend.layouts.footer', function ($view) {
            $view->with('socialLinks', Setting::socialLinks());
        });

        // Home page — hero singleton + the "Top Categories" grid + the four
        // "Popular Courses". All injected here since this markup lives inline in
        // frontend/index.blade.php.
        View::composer('frontend.index', function ($view) {
            $view->with('hero', Hero::current());

            $view->with('homeCategories', Category::onHome()->where('is_active', true)
                ->withCount(['courses' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('department_id')->orderBy('sort_order')->orderBy('id')
                ->get());

            $view->with('popularCourses', Course::active()->popular()
                ->with('category')
                ->take(Course::MAX_POPULAR)
                ->get());

            // Upcoming Events — the cover-flow carousel (3 shown, extras rotate in).
            $view->with('events', Event::active()->forPage('index')->get());

            // Student Success Stories — the "Real Career Stories" card grid.
            $view->with('stories', SuccessStory::active()->forPage('index')->get());

            // Latest Blog — the four posts flagged to show on the home page.
            $view->with('homeBlogs', Blog::active()->forHome()->take(4)->get());
        });

        // Navbar mega-menu (rendered on every page) — departments as columns,
        // their active categories as the list beneath each.
        View::composer('frontend.layouts.header', function ($view) {
            $view->with('megaDepartments', Department::active()
                ->with(['categories' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
                ->get());
        });

        // Trusted Partners — filtered to the page the partial is rendered on
        // (home / about / testimonials), so one logo library serves all three.
        View::composer('frontend.partials.partners', function ($view) {
            $view->with('partners', Partner::active()->forPage($this->currentPage())->get());
        });

        // Statistics counters — home (About section) / about / testimonials.
        View::composer('frontend.partials.counters', function ($view) {
            $view->with('counters', Counter::active()->forPage($this->currentPage())->get());
        });

        // Learner testimonials — home / about / testimonials.
        View::composer('frontend.partials.testimonials', function ($view) {
            $view->with('testimonials', Testimonial::active()->forPage($this->currentPage())->get());
        });

        // Our Journey / Career Success reels — the same reel library feeds the
        // home page and the testimonials page (both @include this partial).
        View::composer('frontend.partials.career-success', function ($view) {
            $view->with('reels', Reel::active()->get());
        });

        // FAQ accordion — home / about / contact. Every active question flagged
        // for the current page is shown; the module is not capped. When a caller
        // already provides $faqs (the course-details page passes that course's
        // own FAQs), the composer stands down and keeps them.
        View::composer('frontend.partials.faq', function ($view) {
            if (array_key_exists('faqs', $view->getData())) {
                return;
            }
            $view->with('faqs', Faq::active()->forPage($this->currentPage())->get());
        });
    }

    /**
     * Frontend route key the shared partials filter on (index | about-us |
     * testimonials | contact-us | courses | course-details). Defaults to the
     * home key when there is no matched route (e.g. console rendering).
     */
    protected function currentPage(): string
    {
        return str_replace('frontend.', '', request()->route()?->getName() ?? 'index');
    }

    /**
     * Define the "api" rate limiter used by routes/api.php (throttle:api):
     * 60 requests per minute, keyed by authenticated user or client IP.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
