<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

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
    public function courses(Request $request)
    {
        // All active courses (client-side filter/search keeps the design's live
        // toolbar working); departments+categories drive the filter panel.
        $courses = Course::active()->with('category.department')->get();

        $departments = Department::active()
            ->with(['categories' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->get();

        // Slug pre-selected from a category card / mega-menu link (?category=…).
        $preselect = $request->query('category');

        return view('frontend.courses', compact('courses', 'departments', 'preselect'));
    }

    /**
     * Course details, resolved by slug. 404 when the course is missing/inactive.
     * "Continue Learning" pulls the flagged courses (excluding this one).
     */
    public function courseDetails(string $slug)
    {
        $course = Course::active()->with(['category.department', 'faqs'])
            ->where('slug', $slug)
            ->firstOrFail();

        $continueLearning = Course::active()->continueLearning()
            ->with('category')
            ->whereKeyNot($course->id)
            ->take(4)
            ->get();

        return view('frontend.course-details', compact('course', 'slug', 'continueLearning'));
    }
}
