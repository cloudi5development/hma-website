<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Blog;
use App\Models\ContactEnquiry;
use App\Models\Course;
use App\Models\CourseEnquiry;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Testimonial;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin dashboard — live KPIs, chips, recent enquiries and a monthly trend,
     * all fed from the enquiry + content tables. The markup/design is unchanged;
     * only the numbers moved from placeholders to the database.
     */
    public function index(): View
    {
        $contactTotal = ContactEnquiry::count();
        $courseTotal  = CourseEnquiry::count();
        $total        = $contactTotal + $courseTotal;

        $today = ContactEnquiry::whereDate('created_at', today())->count()
               + CourseEnquiry::whereDate('created_at', today())->count();

        $pending   = ContactEnquiry::where('status', 'New')->count()
                   + CourseEnquiry::where('status', 'New')->count();
        $contacted = ContactEnquiry::where('status', 'Contacted')->count()
                   + CourseEnquiry::where('status', 'Contacted')->count();
        $closed    = ContactEnquiry::where('status', 'Closed')->count()
                   + CourseEnquiry::where('status', 'Closed')->count();

        // Combined most-recent enquiries (both types), newest first.
        $recentContact = ContactEnquiry::latest()->take(6)->get()->map(fn ($e) => [
            'name'   => $e->name,
            'email'  => $e->email,
            'course' => $e->subject ?: ($e->looking_for ?: 'General enquiry'),
            'type'   => 'Contact',
            'status' => strtolower($e->status),
            'ts'     => $e->created_at,
        ]);
        $recentCourse = CourseEnquiry::latest()->take(6)->get()->map(fn ($e) => [
            'name'   => $e->name,
            'email'  => $e->email,
            'course' => $e->course_name,
            'type'   => 'Course',
            'status' => strtolower($e->status),
            'ts'     => $e->created_at,
        ]);
        $recent = $recentContact->concat($recentCourse)
            ->sortByDesc('ts')
            ->take(6)
            ->map(fn ($r) => $r + ['time' => optional($r['ts'])->diffForHumans()])
            ->values();

        return view('backend.dashboard.index', [
            'stats' => [
                'total'     => $total,
                'course'    => $courseTotal,
                'contact'   => $contactTotal,
                'today'     => $today,
                'pending'   => $pending,
                'contacted' => $contacted,
                'closed'    => $closed,
            ],
            'chipCounts' => [
                'Courses'      => Course::count(),
                'Blog Posts'   => Blog::count(),
                'Testimonials' => Testimonial::count(),
                'Events'       => Event::count(),
                'FAQs'         => Faq::count(),
                'Partners'     => Partner::count(),
            ],
            'recent'   => $recent,
            'activity' => ActivityLog::latest()->take(6)->get(),
            'newVals'  => $this->monthlySeries(),
            'conVals'  => $this->monthlySeries('Closed'),
        ]);
    }

    /**
     * Enquiries per month for the current year (contact + course combined),
     * optionally restricted to a status. Returns a 12-element array (Jan..Dec).
     */
    private function monthlySeries(?string $status = null): array
    {
        $count = function (string $model) use ($status) {
            $q = $model::query()->whereYear('created_at', now()->year);
            if ($status) {
                $q->where('status', $status);
            }
            return $q->selectRaw('MONTH(created_at) as m, COUNT(*) as c')->groupBy('m')->pluck('c', 'm');
        };

        $contact = $count(ContactEnquiry::class);
        $course  = $count(CourseEnquiry::class);

        $out = [];
        for ($m = 1; $m <= 12; $m++) {
            $out[] = (int) ($contact[$m] ?? 0) + (int) ($course[$m] ?? 0);
        }

        return $out;
    }
}
