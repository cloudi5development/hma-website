<?php

namespace App\Http\Controllers\Frontend\Concerns;

use App\Mail\AdminSubmissionNotification;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the site's admin address a copy of what was just submitted.
 *
 * Separate from the visitor's thank-you on purpose: one failing must not stop
 * the other, and neither may ever lose a submission that is already saved —
 * so this reports and returns rather than throwing, exactly like the
 * thank-you calls it sits beside.
 */
trait NotifiesAdminOfSubmission
{
    protected function notifyAdminOfSubmission(Model $submission, string $heading, string $panelUrl): void
    {
        try {
            $recipients = Setting::adminNotificationRecipients();

            if (! $recipients) {
                return;   // nothing configured to send to
            }

            $name    = trim((string) ($submission->name ?? ''));
            $subject = $heading . ($name !== '' ? ' — ' . $name : '');

            Mail::to($recipients)->send(
                new AdminSubmissionNotification($submission, $heading, $subject, $panelUrl),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}
