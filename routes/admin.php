<?php

use App\Http\Controllers\Backend\AuthController;
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

    // Guest auth routes (login form for now; add POST login + forgot/reset
    // password here once authentication is wired up).
    Route::controller(AuthController::class)->name('auth.')->group(function () {
        Route::get('/login', 'login')->name('login');
    });

    // Authenticated admin area — add an auth guard/middleware group here later:
    //
    //   Route::middleware(['auth', 'admin'])->name('template.')->group(function () {
    //       Route::get('/dashboard', DashboardController::class)->name('dashboard');
    //   });

});
