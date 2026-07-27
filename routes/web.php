<?php

use App\Http\Controllers\Frontend\ContactEnquiryController;
use App\Http\Controllers\Frontend\CourseEnquiryController;
use App\Http\Controllers\Frontend\HomeController;
use Illuminate\Support\Facades\Route;

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
