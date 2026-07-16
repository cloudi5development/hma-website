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
    public function courses()
    {
        return view('frontend.courses');
    }

    /**
     * The slug is captured so the card links already point at a real course URL.
     * Nothing looks it up yet — swap in the Course model when it lands and the
     * view keeps working, since it only reads the $course array's keys.
     */
    public function courseDetails(string $slug)
    {
        return view('frontend.course-details', compact('slug'));
    }
}
