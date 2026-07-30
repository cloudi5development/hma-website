<?php

use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ContactEnquiryController;
use App\Http\Controllers\Backend\CounterController;
use App\Http\Controllers\Backend\CourseController;
use App\Http\Controllers\Backend\CourseEnquiryController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\EventController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\HeroController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\PartnerController;
use App\Http\Controllers\Backend\PasswordResetController;
use App\Http\Controllers\Backend\ReelController;
use App\Http\Controllers\Backend\SeoPageController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\SuccessStoryController;
use App\Http\Controllers\Backend\TestimonialController;
use App\Http\Controllers\Backend\UserController;
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

    // Forgot / reset password. Throttled on top of the broker's own per-address
    // throttle, so the form itself cannot be hammered either.
    Route::controller(PasswordResetController::class)->name('auth.password.')->group(function () {
        Route::get('/forgot-password', 'request')->name('request');
        Route::post('/forgot-password', 'email')->middleware('throttle:6,1')->name('email');
        Route::get('/reset-password/{token}', 'reset')->name('reset');
        Route::post('/reset-password', 'update')->middleware('throttle:6,1')->name('update');
    });

    // Authenticated admin area — guarded by the session flag (admin.auth), then
    // by the signed-in user's module permissions (admin.module). The second
    // middleware reads the module off each route's name, so routes added below
    // are guarded automatically as long as they keep the "backend.<module>.*"
    // naming — see App\Support\AdminModules.
    Route::middleware(['admin.auth', 'admin.module'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Topbar notification bell
        Route::get('notifications/{adminNotification}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        // Courses → Departments / Categories / Courses
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('courses', CourseController::class)->except(['show']);

        // Sections → Hero (singleton: overview + edit only)
        Route::get('hero', [HeroController::class, 'index'])->name('hero.index');
        Route::get('hero/edit', [HeroController::class, 'edit'])->name('hero.edit');
        Route::put('hero', [HeroController::class, 'update'])->name('hero.update');

        // Sections → Trusted Partners / Counters / Events / Testimonials / FAQ
        Route::resource('partners', PartnerController::class)->except(['show']);
        Route::resource('counters', CounterController::class)->except(['show']);
        Route::resource('events', EventController::class)->except(['show']);
        Route::resource('success-stories', SuccessStoryController::class)->except(['show']);
        Route::resource('reels', ReelController::class)->except(['show']);
        Route::resource('testimonials', TestimonialController::class)->except(['show']);
        Route::resource('faqs', FaqController::class)->except(['show']);

        // Blog posts (listing + details + home Latest Blog)
        Route::resource('blogs', BlogController::class)->except(['show']);

        // System → Users (admin logins). Listed in AdminModules::SUPER_ADMIN_ONLY,
        // so admin.module lets only the main admin through — creating accounts and
        // handing out module access is theirs alone.
        Route::resource("users", UserController::class)->except(["show"]);

        // System → per-page SEO
        Route::resource("seo-pages", SeoPageController::class)->except(["show"])->parameters(["seo-pages" => "seo_page"]);

        // System → Settings (General / Contact / Social / Email / SEO)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('general', [SettingController::class, 'general'])->name('general');
            Route::put('general', [SettingController::class, 'updateGeneral'])->name('general.update');
            Route::get('logo', [SettingController::class, 'logo'])->name('logo');
            Route::put('logo', [SettingController::class, 'updateLogo'])->name('logo.update');
            Route::get('contact', [SettingController::class, 'contact'])->name('contact');
            Route::put('contact', [SettingController::class, 'updateContact'])->name('contact.update');
            Route::get('social', [SettingController::class, 'social'])->name('social');
            Route::put('social', [SettingController::class, 'updateSocial'])->name('social.update');
            Route::get('email', [SettingController::class, 'email'])->name('email');
            Route::put('email', [SettingController::class, 'updateEmail'])->name('email.update');
            Route::post('email/test', [SettingController::class, 'sendTestMail'])->name('email.test');
            Route::get('seo', [SettingController::class, 'seo'])->name('seo');
            Route::put('seo', [SettingController::class, 'updateSeo'])->name('seo.update');
        });

        // Leads → Contact Enquiry (list + view + status + delete + export)
        Route::get('contact-enquiries/export', [ContactEnquiryController::class, 'export'])->name('contact-enquiries.export');
        Route::get('contact-enquiries/export-excel', [ContactEnquiryController::class, 'exportExcel'])->name('contact-enquiries.export-excel');
        Route::patch('contact-enquiries/{contactEnquiry}/status', [ContactEnquiryController::class, 'updateStatus'])->name('contact-enquiries.status');
        Route::resource('contact-enquiries', ContactEnquiryController::class)->only(['index', 'show', 'destroy']);

        // Leads → Course Enquiry (list + view + status + delete + export; per-course via ?course=)
        Route::get('course-enquiries/export', [CourseEnquiryController::class, 'export'])->name('course-enquiries.export');
        Route::get('course-enquiries/export-excel', [CourseEnquiryController::class, 'exportExcel'])->name('course-enquiries.export-excel');
        Route::patch('course-enquiries/{courseEnquiry}/status', [CourseEnquiryController::class, 'updateStatus'])->name('course-enquiries.status');
        Route::resource('course-enquiries', CourseEnquiryController::class)->only(['index', 'show', 'destroy']);
    });

    // /admin → dashboard when logged in, otherwise the login screen.
    Route::get('/', function () {
        return redirect()->route(
            session('admin_logged_in') ? 'backend.dashboard' : 'backend.auth.login'
        );
    })->name('home');

});
