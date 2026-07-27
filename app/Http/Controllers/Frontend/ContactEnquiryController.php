<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactEnquiryThankYou;
use App\Models\AdminNotification;
use App\Models\ContactEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactEnquiryController extends Controller
{
    /**
     * Store a contact-form submission and send the sender a thank-you email.
     * Called by the shared contact form (home + contact pages), usually via
     * fetch — so it answers with JSON when JSON is expected.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'email'       => ['required', 'email', 'max:190'],
            'phone'       => ['required', 'string', 'max:20'],
            'looking_for' => ['nullable', 'string', 'max:120'],
            'interest'    => ['nullable', 'string', 'max:120'],
            'message'     => ['nullable', 'string', 'max:2000'],
        ]);

        // "Looking for" doubles as the subject line for the admin list.
        $data['subject']    = $data['looking_for'] ?? null;
        $data['ip_address'] = $request->ip();
        $data['status']     = 'New';

        $enquiry = ContactEnquiry::create($data);

        AdminNotification::raise(
            'contact',
            'New Contact Enquiry Received',
            $enquiry->name . ' — ' . ($enquiry->subject ?: 'General enquiry'),
            route('backend.contact-enquiries.show', $enquiry),
        );

        // A mail-server hiccup must never lose the enquiry — it is already saved.
        try {
            Mail::to($enquiry->email)->send(new ContactEnquiryThankYou($enquiry));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('contact_success', true);
    }
}
