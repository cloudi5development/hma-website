<?php

namespace App\Mail;

use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells whoever the form names that a response has arrived.
 *
 * Sent to the addresses configured in the form's own settings, not to a fixed
 * inbox — a job application form and a course enquiry form belong to different
 * people, and the module has no business deciding which.
 */
// NOTE: do NOT implement ShouldQueue. This app runs no queue worker, so a
// queued mailable would sit in the `jobs` table and never be sent — the same
// reason every other mailable here is synchronous.
class FormResponseNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Form $form, public FormResponse $response)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->form->notification_subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.form-response');
    }
}
