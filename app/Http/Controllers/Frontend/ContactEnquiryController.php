<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\NotifiesAdminOfSubmission;
use App\Mail\ContactEnquiryThankYou;
use App\Models\AdminNotification;
use App\Models\ContactEnquiry;
use App\Rules\Recaptcha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactEnquiryController extends Controller
{
    use NotifiesAdminOfSubmission;

    /** The action the page asks Google for; the token is only good for this. */
    private const RECAPTCHA_ACTION = 'contact_enquiry';

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

            // reCAPTCHA v3. The rule stands aside when no keys are configured,
            // so an install without them behaves exactly as it did before.
            'g-recaptcha-response' => [new Recaptcha(self::RECAPTCHA_ACTION, $request->ip())],
        ]);

        // Not a column on the enquiry - it was only ever proof of a person.
        unset($data['g-recaptcha-response']);

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

        // The admin's own copy of the same submission. Sent whatever
        // happened above: the two emails are independent.
        $this->notifyAdminOfSubmission(
            $enquiry,
            'New Contact Enquiry',
            route('backend.contact-enquiries.show', $enquiry),
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('contact_success', true);
    }
}
