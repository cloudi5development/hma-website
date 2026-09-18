<?php

use App\Http\Controllers\Frontend\ContactEnquiryController;
use App\Http\Controllers\Frontend\ContentPageController;
use App\Http\Controllers\Frontend\CourseEnquiryController;
use App\Http\Controllers\Frontend\EventController;
use App\Http\Controllers\Frontend\EventRegistrationController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\SitemapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web (Frontend) Routes
|--------------------------------------------------------------------------
|
| Public-facing website routes. Controllers live in
| App\Http\Controllers\Frontend and views in resources/views/frontend.
| All routes share the "frontend." name prefix.
|
| Backend/admin routes are kept in routes/admin.php and the versioned API in
| routes/api.php — both registered in bootstrap/app.php.
|
*/

Route::controller(HomeController::class)->name('frontend.')->group(function () {
    Route::get('/', 'index')->name('index');
     Route::get('/about-us', 'aboutUs')->name('about-us');
      Route::get('/blog', 'blog')->name('blog');
       // Slug-based, so every blog card links with route('frontend.blog-details', $slug)
       Route::get('/blog/{slug}', 'blogDetails')->name('blog-details');
    Route::get('/contact-us', 'contactUs')->name('contact-us');
     Route::get('/testimonials', 'testimonials')->name('testimonials');
      Route::get('/courses', 'courses')->name('courses');
       // The full "Upcoming Course Schedules" listing — the home-page section
       // shows the first few and links here for the rest. Reads the batches
       // created in Admin → Courses → Create/Edit Course → Course Schedule.
       Route::get('/schedules', 'schedules')->name('schedules');
       // Slug-based, so every course card links with route('frontend.course-details', $slug)
       Route::get('/course/{slug}', 'courseDetails')->name('course-details');
       // The "Brochure" button on the course page. Streamed through the app rather
       // than linked at the stored file so it downloads under a readable name and
       // 404s cleanly when a course has no brochure.
       Route::get('/course/{slug}/brochure', 'brochure')->name('course-brochure');
});

/*
|--------------------------------------------------------------------------
| Sitemap + robots.txt
|--------------------------------------------------------------------------
|
| /sitemap.xml is the URL crawlers and Search Console expect; /sitemap is
| kept as a redirect because it is the one people type by hand.
|
| robots.txt is generated too, so the "Sitemap:" line always points at the
| host the site is running on instead of a hardcoded domain.
|
*/
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('frontend.sitemap');
Route::redirect('sitemap', '/sitemap.xml', 301);
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('frontend.robots');

// Events — the listing reached from the home carousel's "See all", and one
// event per slug behind every "Event Details" button.
Route::controller(EventController::class)->name('frontend.')->group(function () {
    Route::get('/events', 'index')->name('events');
    Route::get('/events/{slug}', 'show')->name('event-details');
});

// Shared contact form (home + contact pages) — stores the enquiry + emails the sender.
Route::post('/contact-enquiry', [ContactEnquiryController::class, 'store'])->name('frontend.contact-enquiry.store');

// Course-details enquiry modal — stores the enquiry + emails the student.
Route::post('/course-enquiry', [CourseEnquiryController::class, 'store'])->name('frontend.course-enquiry.store');

// Event-details "Register for the Event" modal — stores the registration + emails the registrant.
Route::post('/event-registration', [EventRegistrationController::class, 'store'])->name('frontend.event-registration.store');

/*
|--------------------------------------------------------------------------
| Dynamic forms
|--------------------------------------------------------------------------
|
| One route pair serves every form built in Admin → Forms. The slug is the
| only thing that varies, so a new form is published from the panel and needs
| no deploy — there is deliberately no route per form.
|
| The POST is NOT rate-limited. It was, at 20 a minute per IP, and that is a
| ceiling a room full of people reaches on its own: a workshop or a classroom
| sitting a quiz on one Wi-Fi network is one address to the server, and the
| twenty-first person to press Submit was turned away. Bots are met by the
| honeypot field and the CSRF token instead — see PublicFormController.
|
*/
Route::get('/forms/{slug}', [\App\Http\Controllers\Frontend\PublicFormController::class, 'show'])
    ->name('frontend.form.show');
Route::post('/forms/{slug}', [\App\Http\Controllers\Frontend\PublicFormController::class, 'submit'])
    ->name('frontend.form.submit');

// Content Management pages. Declared one by one rather than as /{key} so the
// paths stay reserved and a typo cannot swallow another route.
Route::get('/terms-conditions', [ContentPageController::class, 'show'])
    ->defaults('key', 'terms-conditions')->name('frontend.terms-conditions');
Route::get('/privacy-policy', [ContentPageController::class, 'show'])
    ->defaults('key', 'privacy-policy')->name('frontend.privacy-policy');

/*
|--------------------------------------------------------------------------
| Uploaded file fallback
|--------------------------------------------------------------------------
|
| Everything uploaded through the admin panel is stored on the "public" disk
| and referenced as /storage/<path>. That URL normally resolves through the
| public/storage symlink `php artisan storage:link` creates — but that link is
| gitignored (so it never deploys) and plenty of shared hosts refuse to create
| symlinks at all. Without it every uploaded image 404s on live while the
| seeded images under /assets keep working — exactly the symptom of "images
| don't show after I upload them".
|
| When the symlink is present the web server serves the file itself and never
| reaches Laravel, so this route only ever runs as a fallback. Keep it last.
|
*/
Route::get('storage/{path}', function (string $path) {
    // No traversing out of the uploads directory.
    abort_if(str_contains($path, '..'), 404);

    $disk = Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.+')->name('storage.fallback');
