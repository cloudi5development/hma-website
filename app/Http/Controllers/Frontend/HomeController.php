<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Department;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        // Paginated so the design's Prev / 1 2 / Next controls stay meaningful.
        $blogs = Blog::active()->paginate(12);

        return view('frontend.blog', compact('blogs'));
    }

    /**
     * Blog details, resolved by slug. 404 when the post is missing/inactive.
     * "The Latest" sidebar pulls the flagged posts (excluding this one).
     */
    public function blogDetails(string $slug)
    {
        $blog = Blog::active()->where('slug', $slug)->firstOrFail();

        $latestBlogs = Blog::active()->forLatest()
            ->whereKeyNot($blog->id)
            ->take(3)
            ->get();

        return view('frontend.blog-details', compact('blog', 'latestBlogs'));
    }
    public function contactUs(Request $request)
    {
        // "Register Now" on an event details page links here as ?event=<slug>,
        // so the enquiry form opens already naming the event. An unknown or
        // unpublished slug simply resolves to null and the form is untouched.
        $slug = trim((string) $request->query('event'));

        $enquiryEvent = $slug === ''
            ? null
            : Event::active()->where('slug', $slug)->first();

        return view('frontend.contact-us', compact('enquiryEvent'));
    }
    public function testimonials()
    {
        return view('frontend.testimonials');
    }
    public function courses(Request $request)
    {
        // All active courses (client-side filter/search keeps the design's live
        // toolbar working); departments+categories drive the filter panel.
        //
        // nextSchedule is the soonest upcoming batch, which is where each card
        // now gets its mode from — eager-loaded, or it is a query per card.
        $courses = Course::active()->with(['category.department', 'nextSchedule'])->get();

        $departments = Department::active()
            ->with(['categories' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id')])
            ->get();

        // Slug pre-selected from a category card / mega-menu link (?category=…).
        $preselect = $request->query('category');

        return view('frontend.courses', compact('courses', 'departments', 'preselect'));
    }

    /**
     * Every upcoming batch, soonest first — the full version of the home page's
     * "Upcoming Course Schedules" section, which shows the first few and links
     * here. Same rules as the home query: the course active, the batch active,
     * starting in the future and not yet ended.
     *
     * Queried from the schedule side so batches from different courses interleave
     * by date, with the course and category eager-loaded to keep it at three
     * queries however many batches come back.
     */
    public function schedules()
    {
        $schedules = CourseSchedule::active()->upcoming()
            ->whereHas('course', fn ($q) => $q->where('is_active', true))
            ->with(['course' => fn ($q) => $q->select('id', 'category_id', 'name', 'slug', 'image', 'duration')
                ->with(['category' => fn ($c) => $c->select('id', 'name', 'slug')])])
            ->orderBy('start_date')->orderBy('id')
            ->get();

        return view('frontend.schedules', compact('schedules'));
    }

    /**
     * Course details, resolved by slug. 404 when the course is missing/inactive.
     * "Continue Learning" pulls the flagged courses (excluding this one).
     */
    public function courseDetails(string $slug)
    {
        // The course's own upcoming batches (Admin → Courses → Schedule) ride
        // along with the eager load, so the "Upcoming Batches" block on the page
        // costs no extra query. Same rules as the home section: the batch active,
        // starting in the future and not yet ended.
        $course = Course::active()
            ->with([
                'category.department',
                'faqs',
                'schedules' => fn ($q) => $q->active()->upcoming(),
                // The soonest of those — the hero's dates and the "Mode" stat.
                'nextSchedule',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $continueLearning = Course::active()->continueLearning()
            ->with(['category', 'nextSchedule'])
            ->whereKeyNot($course->id)
            ->take(4)
            ->get();

        return view('frontend.course-details', compact('course', 'slug', 'continueLearning'));
    }

    /**
     * Download a course's brochure PDF (Admin → Courses → Course Brochure).
     *
     * Served through the app so the file downloads under a readable name — the
     * stored file has a random one — and so a course without a brochure, or a row
     * whose file has gone missing from disk, 404s instead of serving nothing.
     */
    public function brochure(string $slug)
    {
        $course = Course::active()->where('slug', $slug)->firstOrFail();

        abort_unless($course->has_brochure, 404);

        $headers = ['Content-Type' => 'application/pdf'];

        // Read from whichever place the path points at — the "public" disk for an
        // admin upload (which does not need the storage symlink to exist), or
        // straight off /public for a seeded asset.
        return $course->brochureOnPublicDisk()
            ? Storage::disk('public')->download($course->brochureDiskPath(), $course->brochure_filename, $headers)
            : response()->download(public_path($course->brochure), $course->brochure_filename, $headers);
    }
}
