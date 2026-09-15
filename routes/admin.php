<?php

use App\Http\Controllers\Backend\AboutSectionController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ContactEnquiryController;
use App\Http\Controllers\Backend\ContentPageController;
use App\Http\Controllers\Backend\CounterController;
use App\Http\Controllers\Backend\CourseBulkUploadController;
use App\Http\Controllers\Backend\CourseController;
use App\Http\Controllers\Backend\CourseEnquiryController;
use App\Http\Controllers\Backend\EventRegistrationController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartmentController;
use App\Http\Controllers\Backend\EventController;
use App\Http\Controllers\Backend\FaqController;
use App\Http\Controllers\Backend\FormBulkUploadController;
use App\Http\Controllers\Backend\FormController;
use App\Http\Controllers\Backend\FormResponseController;
use App\Http\Controllers\Backend\HeroController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\PartnerController;
use App\Http\Controllers\Backend\PasswordResetController;
use App\Http\Controllers\Backend\ReelController;
use App\Http\Controllers\Backend\ScheduleController;
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

        // Courses → Departments / Categories / Courses / Schedule
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);

        // Courses → Bulk Upload. Registered BEFORE the courses resource so
        // "courses/bulk-upload" can never be swallowed by a {course} segment,
        // and named "courses.bulk.*" so admin.module resolves it to the same
        // "courses" module the rest of the screen uses — no new permission key.
        Route::prefix('courses/bulk-upload')->name('courses.bulk.')
            ->controller(CourseBulkUploadController::class)->group(function () {
                Route::get('/', 'form')->name('form');
                Route::get('template', 'template')->name('template');
                Route::get('export', 'export')->name('export');
                Route::post('validate', 'check')->name('validate');
                Route::get('preview', 'preview')->name('preview');
                Route::post('import', 'import')->name('import');
                Route::get('summary', 'summary')->name('summary');
                Route::get('errors', 'errors')->name('errors');
                Route::post('cancel', 'cancel')->name('cancel');
            });

        Route::resource('courses', CourseController::class)->except(['show']);

        // Upcoming batches, across every course. Bound to CourseSchedule under
        // the shorter "schedule" parameter, which keeps the module key (and so
        // the permission it is granted under) "schedules".
        Route::resource('schedules', ScheduleController::class)
            ->except(['show'])
            ->parameters(['schedules' => 'schedule']);

        // Sections → Hero (singleton: overview + edit only)
        Route::get('hero', [HeroController::class, 'index'])->name('hero.index');
        Route::get('hero/edit', [HeroController::class, 'edit'])->name('hero.edit');
        Route::put('hero', [HeroController::class, 'update'])->name('hero.update');

        // Sections → About Us (the four blocks on the About page: Our Story,
        // Our Purpose, Our Features, Our Approach). Seeded and keyed, so this is
        // an overview plus edit/update — nothing is created or deleted.
        Route::get('about-sections', [AboutSectionController::class, 'index'])->name('about-sections.index');
        Route::get('about-sections/{key}/edit', [AboutSectionController::class, 'edit'])->name('about-sections.edit');
        Route::put('about-sections/{key}', [AboutSectionController::class, 'update'])->name('about-sections.update');

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

        /*
         * Forms — the dynamic form builder.
         *
         * Everything is named "forms.*" so admin.module resolves the whole
         * module, responses included, to the one "forms" permission. The extra
         * verbs are declared BEFORE the resource so a literal segment like
         * "forms/export" can never be swallowed by the {form} parameter.
         */
        // Forms → Responses: every response across every form. Its own path
        // rather than "forms/responses", which the {form} parameter below would
        // otherwise try to resolve as a model.
        Route::get('form-responses', [FormResponseController::class, 'all'])->name('forms.all-responses');
        // Empties whatever the Responses screen is currently filtered to.
        Route::delete('form-responses', [FormResponseController::class, 'clearAll'])->name('forms.clear-responses');
        // Deleting one response without naming its form — a response can outlive
        // its form when the cascade is not enforced, and it must still be
        // removable. See FormResponseController.
        Route::delete('form-responses/{response}', [FormResponseController::class, 'destroyAny'])->name('forms.response-destroy');

        Route::prefix('forms')->name('forms.')->group(function () {
            // Asked by the builder's "Generate Link" dialog, so the address it
            // shows is the one the form will really be created with.
            Route::post('slug-preview', [FormController::class, 'slugPreview'])->name('slug-preview');

            // The builder's Bulk Upload. Neither writes: the preview checks a
            // sheet and returns its questions for the builder to add, and the
            // form's own Save stores them. Declared before the resource below
            // so "forms/bulk-template" is not read as a form id.
            Route::get('bulk-template', [FormBulkUploadController::class, 'template'])->name('bulk-template');
            Route::post('bulk-preview', [FormBulkUploadController::class, 'preview'])->name('bulk-preview');

            Route::post('{form}/toggle', [FormController::class, 'toggle'])->name('toggle');
            // Settings live on the form's own page, not on the builder — see
            // FormController::updateSettings.
            Route::put('{form}/settings', [FormController::class, 'updateSettings'])->name('settings');
            Route::post('{form}/duplicate', [FormController::class, 'duplicate'])->name('duplicate');
            Route::get('{form}/preview', [FormController::class, 'preview'])->name('preview');
            Route::post('{form}/preview', [FormController::class, 'previewSubmit'])->name('preview.submit');

            Route::prefix('{form}/responses')->name('responses.')->group(function () {
                Route::get('/', [FormResponseController::class, 'index'])->name('index');
                // Empties this form's responses — declared before {response}
                // so it is not read as a response id.
                Route::delete('/', [FormResponseController::class, 'clear'])->name('clear');
                Route::get('export', [FormResponseController::class, 'export'])->name('export');
                Route::get('export-excel', [FormResponseController::class, 'exportExcel'])->name('export-excel');
                // Uploaded files are held on the private disk, so this route is
                // the only way to them — and it is inside the admin guard.
                Route::get('{response}/file/{value}/{index?}', [FormResponseController::class, 'download'])->name('file');
                Route::get('{response}', [FormResponseController::class, 'show'])->name('show');
                Route::patch('{response}/status', [FormResponseController::class, 'updateStatus'])->name('status');
                Route::delete('{response}', [FormResponseController::class, 'destroy'])->name('destroy');
            });
        });

        Route::resource('forms', FormController::class);

        // System → Users (admin logins). Listed in AdminModules::SUPER_ADMIN_ONLY,
        // so admin.module lets only the main admin through — creating accounts and
        // handing out module access is theirs alone.
        Route::resource("users", UserController::class)->except(["show"]);

        // System → per-page SEO
        Route::resource("seo-pages", SeoPageController::class)->except(["show"])->parameters(["seo-pages" => "seo_page"]);

        // Content Management → the written pages (Terms & Conditions, Privacy
        // Policy). Edit + update only: the rows are seeded by the migration and
        // the public routes are fixed, so there is nothing to create or delete.
        Route::prefix('content')->name('content-pages.')->group(function () {
            Route::get('{key}', [ContentPageController::class, 'edit'])->name('edit');
            Route::put('{key}', [ContentPageController::class, 'update'])->name('update');
        });

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

        // Leads → Event Registration (list + view + status + delete + export; per-event via ?event=)
        Route::get('event-registrations/export', [EventRegistrationController::class, 'export'])->name('event-registrations.export');
        Route::get('event-registrations/export-excel', [EventRegistrationController::class, 'exportExcel'])->name('event-registrations.export-excel');
        Route::patch('event-registrations/{eventRegistration}/status', [EventRegistrationController::class, 'updateStatus'])->name('event-registrations.status');
        Route::resource('event-registrations', EventRegistrationController::class)->only(['index', 'show', 'destroy']);
    });

    // /admin → dashboard when logged in, otherwise the login screen.
    Route::get('/', function () {
        return redirect()->route(
            session('admin_logged_in') ? 'backend.dashboard' : 'backend.auth.login'
        );
    })->name('home');

});
