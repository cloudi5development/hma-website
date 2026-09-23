<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the admin that somebody has submitted a contact enquiry, a course
 * enquiry or an event registration, and prints what they sent.
 *
 * One mailable for all three: what differs is the heading, the subject and the
 * link into the panel, all of which the caller supplies, while the details
 * come off the record itself (SummarisesForAdminEmail). The dynamic Form
 * Module keeps its own FormResponseNotification — a form's questions are its
 * own, and that mailable already renders them.
 */
// NOTE: do NOT implement ShouldQueue. This app runs no queue worker, so a
// queued mailable would sit in the `jobs` table and never be sent — the same
// reason every other mailable here is synchronous.
class AdminSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Model  $submission  The saved enquiry / registration.
     * @param string $heading     "New Course Enquiry", and so on.
     * @param string $subjectLine The email's subject line.
     * @param string $panelUrl    Where the admin opens it.
     */
    public function __construct(
        public Model $submission,
        public string $heading,
        public string $subjectLine,
        public string $panelUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->subjectLine);

        // Hitting reply should answer the person who wrote in, not the site's
        // own inbox. Only when they gave a usable address.
        $email = trim((string) ($this->submission->email ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $envelope->replyTo($email, trim((string) ($this->submission->name ?? '')) ?: $email);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-submission');
    }
}
