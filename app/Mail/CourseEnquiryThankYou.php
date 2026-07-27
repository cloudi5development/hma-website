<?php

namespace App\Mail;

use App\Models\CourseEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// NOTE: do NOT implement ShouldQueue — send synchronously (no queue worker runs).
class CourseEnquiryThankYou extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CourseEnquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your enquiry about ' . $this->enquiry->course_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-thank-you',
        );
    }
}
