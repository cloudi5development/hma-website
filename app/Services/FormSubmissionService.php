<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Validates and stores one submission of any admin-built form.
 *
 * Everything here is derived from the form's own fields at request time. There
 * is no per-form code, and adding a question to a form in the panel changes what
 * this validates on the very next submission.
 *
 * Two rules worth stating because they are easy to get wrong:
 *
 * 1. A field hidden by conditional logic is not required. The visitor never saw
 *    it, so holding them to it would make the form impossible to submit.
 *
 * 2. Uploads go to the PRIVATE disk. Everything else this app stores goes to the
 *    "public" disk and is served straight off a URL — fine for a course
 *    thumbnail, wrong for somebody's resume. These are served instead through an
 *    admin-only download route.
 */
class FormSubmissionService
{
    /** Where uploads live on the private disk. */
    public const UPLOAD_ROOT = 'form-uploads';

    /**
     * The honeypot input's name. A real visitor never sees it and leaves it
     * empty; a bot that fills every input in the document fills this one too.
     */
    public const HONEYPOT = 'website_url';

    /**
     * What was submitted, cleaned the way it is checked and stored.
     *
     * Today that is the mobile numbers: "+91 98765 43210" becomes 9876543210,
     * so the 10-digit rule judges the number and not its formatting, and every
     * response holds the number in one form. Run before validator() — by the
     * live form and the preview alike.
     */
    public function normalise(Form $form, array $input): array
    {
        foreach ($form->fields as $field) {
            if ($field->field_type === \App\Support\FormFieldType::MOBILE && array_key_exists($field->field_key, $input)) {
                $input[$field->field_key] = FormField::normaliseMobile($input[$field->field_key]);
            }
        }

        return $input;
    }

    /**
     * Build the validator for a form against what was submitted.
     *
     * Public so the preview can run the exact same checks without writing
     * anything — the admin testing a form has to be testing the real rules.
     */
    public function validator(Form $form, array $input, array $files = []): ValidatorContract
    {
        $rules    = [];
        $messages = [];

        foreach ($form->fields as $field) {
            // A hidden field is set by the page, not the visitor, so it is never
            // held to a required rule whatever the builder says.
            $required = $field->is_required
                && ! $field->isHidden()
                && $field->isVisibleFor($input);

            $rules += $field->validationRules($required);
            $messages += $field->validationMessages();
        }

        return Validator::make($input + $files, $rules, $messages);
    }

    /**
     * Store a validated submission.
     *
     * @param  array  $input  the validated non-file values
     * @return FormResponse
     */
    public function store(Form $form, array $input, Request $request): FormResponse
    {
        $response = DB::transaction(function () use ($form, $input, $request) {
            $response = $form->responses()->create([
                'status'       => FormResponse::STATUSES[0],
                'ip_address'   => $request->ip(),
                'submitted_at' => now(),
            ]);

            $order = 0;

            foreach ($form->fields as $field) {
                // Skip a field the visitor never saw — recording an empty answer
                // for it would put a blank column in every export.
                if (! $field->isVisibleFor($input)) {
                    continue;
                }

                $value = $this->valueFor($field, $input, $request);

                if ($value === null) {
                    continue;
                }

                $response->values()->create([
                    // The snapshot alongside the key: see FormResponseValue.
                    'field_id'    => $field->id,
                    'field_key'   => $field->field_key,
                    'field_label' => $field->label,
                    'field_type'  => $field->field_type,
                    'value'       => $value,
                    'sort_order'  => $order++,
                ]);
            }

            return $response;
        });

        $this->notify($form, $response);

        return $response;
    }

    /**
     * One field's answer, as the column stores it: a string, or a JSON list for
     * the multi-answer types. Null means "nothing was answered", and no row is
     * written at all.
     */
    private function valueFor(FormField $field, array $input, Request $request): ?string
    {
        if ($field->isFile()) {
            return $this->storeFiles($field, $request);
        }

        if ($field->isGrid()) {
            return $this->gridValue($field, $input);
        }

        $raw = $input[$field->field_key] ?? null;

        if ($field->isHidden() && blank($raw)) {
            // A hidden field falls back to its configured default, which is the
            // only way a campaign/source value gets recorded at all.
            $raw = $field->default_value;
        }

        if (is_array($raw)) {
            $values = array_values(array_filter(array_map('strval', $raw), fn ($v) => $v !== ''));

            return $values ? json_encode($values) : null;
        }

        return blank($raw) ? null : (string) $raw;
    }

    /**
     * A grid's answers, as JSON keyed by row.
     *
     * The page posts them keyed by the row's POSITION — a row labelled
     * "Support / Service" cannot be a validation key — so the position is
     * resolved back to the row's stored value here, at the one point that knows
     * both. What lands in the column is
     * {"Product":"Excellent","Service":"Good"}, which reads correctly forever
     * after even if the rows are later reordered.
     */
    private function gridValue(FormField $field, array $input): ?string
    {
        $answers = $input[$field->field_key] ?? null;

        if (! is_array($answers)) {
            return null;
        }

        $byRow = [];

        foreach ($field->rows as $index => $row) {
            $answer = $answers[$index] ?? null;

            if (is_array($answer)) {
                $answer = array_values(array_filter(array_map('strval', $answer), fn ($v) => $v !== ''));

                if ($answer) {
                    $byRow[$row->value] = $answer;
                }

                continue;
            }

            if (filled($answer)) {
                $byRow[$row->value] = (string) $answer;
            }
        }

        return $byRow ? json_encode($byRow) : null;
    }

    /**
     * Move a field's uploads onto the private disk and return their paths.
     *
     * Files are stored as "<random>__<original name>": the random part is the
     * real filename so nothing a visitor typed becomes a path, and the readable
     * part rides along so the admin's download is not called "8f3c1d….pdf". The
     * original is sanitised before it is appended — it is untrusted text that
     * ends up in a Content-Disposition header.
     */
    private function storeFiles(FormField $field, Request $request): ?string
    {
        $uploads = $request->file($field->field_key);

        if (! $uploads) {
            return null;
        }

        $paths = [];

        foreach ((is_array($uploads) ? $uploads : [$uploads]) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $safe = Str::limit(
                Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file',
                60,
                '',
            ) . '.' . strtolower($file->getClientOriginalExtension());

            $paths[] = $file->storeAs(
                self::UPLOAD_ROOT . '/' . $field->form_id,
                Str::random(24) . '__' . $safe,
                'local',
            );
        }

        if (! $paths) {
            return null;
        }

        return $field->allowsMultipleFiles() ? json_encode($paths) : $paths[0];
    }

    /* ============================ NOTIFICATION ============================= */

    /**
     * Raise the panel's bell, and email whoever the form names.
     *
     * Wrapped so a mail-server hiccup can never lose a submission that is
     * already saved — the same guard the enquiry controllers use.
     */
    private function notify(Form $form, FormResponse $response): void
    {
        try {
            AdminNotification::raise(
                'form',
                'New response — ' . $form->title,
                Str::limit($this->summarise($response), 120),
                route('backend.forms.responses.show', [$form, $response]),
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $recipients = $form->notificationRecipients();

        if (! $recipients) {
            return;
        }

        try {
            Mail::to($recipients)->send(
                new \App\Mail\FormResponseNotification($form, $response->load(FormResponse::WITH_ANSWERS)),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * The first couple of answers, for the notification line.
     *
     * The values were just written, so the relation is loaded fresh — including
     * field.options, which is what turns a stored choice value back into the
     * label the admin wrote.
     */
    private function summarise(FormResponse $response): string
    {
        $response->load(FormResponse::WITH_ANSWERS);

        return $this->summaryLine($response);
    }

    private function summaryLine(FormResponse $response): string
    {
        return $response->values
            ->take(2)
            ->map(fn ($value) => $value->field_label . ': ' . $value->display)
            ->implode(' · ') ?: 'View the response';
    }

    /* =============================== UPLOADS =============================== */

    /** Delete a response's uploads. Called when the response itself is deleted. */
    public static function deleteUploads(FormResponse $response): void
    {
        foreach ($response->values as $value) {
            foreach ($value->filePaths() as $path) {
                // Never step outside the module's own directory, whatever the
                // stored string says.
                if (str_starts_with($path, self::UPLOAD_ROOT . '/')) {
                    Storage::disk('local')->delete($path);
                }
            }
        }
    }
}
