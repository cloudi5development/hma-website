<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * Display the admin login page.
     */
    public function login()
    {
        return view('backend.auth.login');
    }
}
