<?php

namespace App\Mail;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// NOTE: do NOT implement ShouldQueue. This app has no queue worker running, so
// Mail::to()->send() must dispatch synchronously — a ShouldQueue mailable would
// silently sit in the `jobs` table and never reach the recipient.
class ContactEnquiryThankYou extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactEnquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thanks for reaching out to Hire Minds Academy',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-thank-you',
        );
    }
}
