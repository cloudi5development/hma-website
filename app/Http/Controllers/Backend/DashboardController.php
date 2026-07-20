<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin dashboard. Placeholder for now — the real KPI/chart dashboard is
     * Phase 6 of the admin build. Protected by the admin.auth middleware.
     */
    public function index(): View
    {
        return view('backend.dashboard.index');
    }
}
