<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\EventRegistrationThankYou;
use App\Models\AdminNotification;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class EventRegistrationController extends Controller
{
    /**
     * Store a submission from the "Register for the Event" modal and email the
     * registrant a thank-you. Submitted via fetch, so it answers JSON.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'event_id'            => ['required', 'integer', 'exists:events,id'],
            'name'                => ['required', 'string', 'max:120'],
            'email'               => ['required', 'email', 'max:190'],
            'phone'               => ['required', 'string', 'max:20'],
            'city'                => ['nullable', 'string', 'max:120'],
            'professional_status' => ['nullable', Rule::in(EventRegistration::PROFESSIONAL_STATUSES)],
            'organisation'        => ['nullable', 'string', 'max:150'],
            // The modal will not submit without the box ticked; enforced here too
            // so the record can never be created without the consent on it.
            'agreed_terms'        => ['accepted'],
        ], [
            'agreed_terms.accepted' => 'Please accept the Terms & Conditions to register.',
        ]);

        // Only a published event can be registered for — a direct POST must not
        // be able to book a seat on a draft.
        $event = Event::active()->findOrFail($data['event_id']);

        $registration = EventRegistration::create([
            'event_id'            => $event->id,
            'event_title'         => $event->title,   // snapshot, survives a later rename/delete
            'name'                => $data['name'],
            'email'               => $data['email'],
            'phone'               => $data['phone'],
            'city'                => $data['city'] ?? null,
            'professional_status' => $data['professional_status'] ?? null,
            'organisation'        => $data['organisation'] ?? null,
            'agreed_terms'        => true,
            'ip_address'          => $request->ip(),
            'status'              => 'New',
        ]);

        AdminNotification::raise(
            'event',
            'New Event Registration Received',
            $registration->name . ' — ' . $registration->event_title,
            route('backend.event-registrations.show', $registration),
        );

        // A mail hiccup must never lose the registration — it is already saved.
        try {
            Mail::to($registration->email)->send(new EventRegistrationThankYou($registration));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('event_registration_success', true);
    }
}
