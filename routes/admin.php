<?php

use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\CounterController;
use App\Http\Controllers\Backend\CourseController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\HeroController;
use App\Http\Controllers\Backend\PartnerController;
use App\Http\Controllers\Backend\TestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backend (Admin Panel) Routes
|--------------------------------------------------------------------------
|
| Mounted by bootstrap/app.php inside the "web" middleware group. Everything
| here is prefixed with /admin and shares the "backend." name prefix, so the
| frontend and admin never collide as the panel grows.
|
| Controllers live in App\Http\Controllers\Backend, views in
| resources/views/backend.
|
*/

Route::prefix('admin')->name('backend.')->group(function () {

    // Guest auth — login form + credential check + logout.
    Route::controller(AuthController::class)->name('auth.')->group(function () {
        Route::get('/login', 'login')->name('login');
        Route::post('/login', 'authenticate')->name('authenticate');
        Route::post('/logout', 'logout')->name('logout');
    });

    // Authenticated admin area — guarded by the session flag (admin.auth).
    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Courses → Departments / Categories / Courses
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('courses', CourseController::class)->except(['show']);

        // Sections → Hero (singleton: overview + edit only)
        Route::get('hero', [HeroController::class, 'index'])->name('hero.index');
        Route::get('hero/edit', [HeroController::class, 'edit'])->name('hero.edit');
        Route::put('hero', [HeroController::class, 'update'])->name('hero.update');

        // Sections → Trusted Partners / Counters / Testimonials / FAQ
        Route::resource('partners', PartnerController::class)->except(['show']);
        Route::resource('counters', CounterController::class)->except(['show']);
        Route::resource('testimonials', TestimonialController::class)->except(['show']);
        Route::resource('faqs', FaqController::class)->except(['show']);
    });

    // /admin → dashboard when logged in, otherwise the login screen.
    Route::get('/', function () {
        return redirect()->route(
            session('admin_logged_in') ? 'backend.dashboard' : 'backend.auth.login'
        );
    })->name('home');

});
