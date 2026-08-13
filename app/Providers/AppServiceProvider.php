<?php

namespace App\Providers;

use App\Models\AboutSection;
use App\Models\AdminNotification;
use App\Models\Blog;
use App\Models\Category;
use App\Models\ContentPage;
use App\Models\Counter;
use App\Models\Course;
use App\Models\CourseSchedule;
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
use Illuminate\Auth\Notifications\ResetPassword;
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

        // The password-reset email links into the ADMIN panel. Laravel's default
        // points at a route named "password.reset", which this app does not have —
        // every admin route is named "backend.*" — so the URL is built explicitly.
        ResetPassword::createUrlUsing(fn ($user, string $token) => route(
            'backend.auth.password.reset',
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()]
        ));

        // Admin topbar bell. The panel has an Unread and a Read tab, so each list
        // is fetched separately rather than filtered in the view — opening a
        // notification marks it read, which moves it from one tab to the other.
        View::composer('backend.template.layouts.header', function ($view) {
            $view->with('adminNotifUnreadList', AdminNotification::unread()->latest()->take(10)->get());
            $view->with('adminNotifReadList', AdminNotification::read()->latest()->take(10)->get());
            $view->with('adminNotifUnread', AdminNotification::unread()->count());
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

        // Social profile links (Settings → Social Media) for the footer icons,
        // plus the footer's "Our Courses" column — the first five active courses,
        // each linking to its own page instead of the dead "#" the column used to
        // carry. Empty (no courses yet) hides the column.
        View::composer('frontend.layouts.footer', function ($view) {
            $view->with('socialLinks', Setting::socialLinks());
            $view->with('footerCourses', Course::active()->take(5)->get(['name', 'slug']));

            // The legal links, from Content Management. Ordered in PHP rather
            // than with an ORDER BY FIELD(): that is MySQL-only, and the footer
            // renders on every page including under the sqlite test driver.
            $order = ['privacy-policy', 'terms-conditions'];

            $view->with('legalPages', ContentPage::active()
                ->whereIn('key', array_keys(ContentPage::PAGES))
                ->get()
                ->sortBy(fn ($page) => array_search($page->key, $order, true))
                ->values());
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

            // Popular Courses — a slider now rather than a row of four, so every
            // flagged course is sent and the deck carries as many as the admin
            // marks. Their thumbnails are lazy-loaded, so a long list costs the
            // visitor nothing until they scroll it.
            $view->with('popularCourses', Course::active()->popular()
                ->with('category')
                ->get());

            // Upcoming Course Schedules — the batches created in Admin →
            // Courses → Schedule. Queried from the schedule side so the soonest
            // batches win across all courses, with the course and its category
            // eager-loaded (and only the columns the section prints) so the
            // table costs three queries however many rows come back.
            $view->with('courseSchedules', CourseSchedule::active()->upcoming()
                ->whereHas('course', fn ($q) => $q->where('is_active', true))
                ->with(['course' => fn ($q) => $q->select('id', 'category_id', 'name', 'slug', 'image', 'duration')
                    ->with(['category' => fn ($c) => $c->select('id', 'name', 'slug')])])
                ->orderBy('start_date')->orderBy('id')
                ->take(CourseSchedule::MAX_HOME)
                ->get());

            // Upcoming Events — the cover-flow carousel (3 shown, extras rotate in).
            $view->with('events', Event::active()->visibleOn('index')->get());

            // Student Success Stories — the "Real Career Stories" card grid.
            $view->with('stories', SuccessStory::active()->visibleOn('index')->get());

            // Latest Blog — the four posts flagged to show on the home page.
            $view->with('homeBlogs', Blog::active()->forHome()->take(4)->get());
        });

        // About Us page — Our Story / Our Purpose / Our Features / Our Approach.
        // Keyed by section key so the markup can reach for the one it is drawing;
        // a section switched off in the panel is simply absent from the map and
        // the page leaves its block out.
        View::composer('frontend.about-us', function ($view) {
            $view->with('aboutSections', AboutSection::active()
                ->with(['items' => fn ($q) => $q->where('is_active', true)])
                ->get()
                ->keyBy('key'));
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
            $view->with('partners', Partner::active()->visibleOn($this->currentPage())->get());
        });

        // Statistics counters — home (About section) / about / testimonials.
        View::composer('frontend.partials.counters', function ($view) {
            $view->with('counters', Counter::active()->visibleOn($this->currentPage())->get());
        });

        // Learner testimonials — home / about / testimonials.
        View::composer('frontend.partials.testimonials', function ($view) {
            $view->with('testimonials', Testimonial::active()->visibleOn($this->currentPage())->get());
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
            $view->with('faqs', Faq::active()->visibleOn($this->currentPage())->get());
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
