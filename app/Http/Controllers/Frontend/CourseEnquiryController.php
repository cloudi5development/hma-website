<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
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

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('course_enquiry_success', true);
    }
}
