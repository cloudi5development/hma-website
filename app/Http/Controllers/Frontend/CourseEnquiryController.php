<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\NotifiesAdminOfSubmission;
use App\Rules\Recaptcha;
use App\Mail\CourseEnquiryThankYou;
use App\Models\AdminNotification;
use App\Models\Course;
use App\Models\CourseEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CourseEnquiryController extends Controller
{
    use NotifiesAdminOfSubmission;

    /** The action the page asks Google for; the token is only good for this. */
    private const RECAPTCHA_ACTION = 'course_enquiry';

    /**
     * Store a course-enquiry (the "Take the First Step" modal on course-details)
     * and email the student a thank-you. Submitted via fetch, so it answers JSON.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'course_id'   => ['required', 'integer', 'exists:courses,id'],
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['required', 'email', 'max:190'],
            'phone'       => ['required', 'string', 'max:20'],
            'city'        => ['nullable', 'string', 'max:120'],
            'career_goal' => ['nullable', 'string', 'max:120'],
            'message'     => ['nullable', 'string', 'max:2000'],
            // Only the Apply buttons on a schedule row send this; it is a label
            // the page rendered, so it is length-capped and stored as typed.
            'batch'       => ['nullable', 'string', 'max:120'],
            // reCAPTCHA v3. The rule stands aside when no keys are configured,
            // so an install without them behaves exactly as it did before.
            'g-recaptcha-response' => [new Recaptcha(self::RECAPTCHA_ACTION, $request->ip())],
        ]);

        $course = Course::findOrFail($data['course_id']);

        $enquiry = CourseEnquiry::create([
            'course_id'   => $course->id,
            'course_name' => $course->name,      // snapshot, survives a later course rename/delete
            'batch'       => $data['batch'] ?? null,
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'city'        => $data['city'] ?? null,
            'career_goal' => $data['career_goal'] ?? null,
            'message'     => $data['message'] ?? null,
            'ip_address'  => $request->ip(),
            'status'      => 'New',
        ]);

        AdminNotification::raise(
            'course',
            'New Course Enquiry Received',
            $enquiry->name . ' — ' . $enquiry->course_name,
            route('backend.course-enquiries.show', $enquiry),
        );

        // A mail hiccup must never lose the enquiry — it is already saved.
        try {
            Mail::to($enquiry->email)->send(new CourseEnquiryThankYou($enquiry));
        } catch (\Throwable $e) {
            report($e);
        }

        // The admin's own copy of the same submission. Sent whatever
        // happened above: the two emails are independent.
        $this->notifyAdminOfSubmission(
            $enquiry,
            'New Course Enquiry',
            route('backend.course-enquiries.show', $enquiry),
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('course_enquiry_success', true);
    }
}
