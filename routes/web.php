<?php

use App\Http\Controllers\Frontend\ContactEnquiryController;
use App\Http\Controllers\Frontend\CourseEnquiryController;
use App\Http\Controllers\Frontend\HomeController;
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
       // Slug-based, so every course card links with route('frontend.course-details', $slug)
       Route::get('/course/{slug}', 'courseDetails')->name('course-details');
});

// Shared contact form (home + contact pages) — stores the enquiry + emails the sender.
Route::post('/contact-enquiry', [ContactEnquiryController::class, 'store'])->name('frontend.contact-enquiry.store');

// Course-details enquiry modal — stores the enquiry + emails the student.
Route::post('/course-enquiry', [CourseEnquiryController::class, 'store'])->name('frontend.course-enquiry.store');

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
