<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Display the frontend home page.
     */
    public function index()
    {
        return view('frontend.index');
    }
    public function aboutUs()
    {
        return view('frontend.about-us');
    }
    public function blog()
    {
        return view('frontend.blog');
    }
    public function blogDetails()
    {
        return view('frontend.blog-details');
    }
    public function contactUs()
    {
        return view('frontend.contact-us');
    }
    public function testimonials()
    {
        return view('frontend.testimonials');
    }
}
