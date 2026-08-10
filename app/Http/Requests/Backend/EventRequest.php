<?php

namespace App\Http\Requests\Backend;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the image is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');
        $event = $this->route('event');
        $id = $event?->id;

        // The form offers the fixed TYPES list. An event saved before the field
        // became a dropdown may hold its own wording ("Live Event"), and the
        // form still shows it, so accept that one value back unchanged.
        $types = array_values(array_unique(array_merge(
            Event::TYPES,
            array_filter([$event?->type]),
        )));

        return [
            'speaker'    => ['required', 'string', 'max:120'],
            'title'      => ['required', 'string', 'max:160'],
            // Schedule — all optional; the card leaves out whichever row is blank.
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'location'   => ['nullable', 'string', 'max:255'],
            'type'       => ['required', Rule::in($types)],
            'price'      => ['nullable', 'string', 'max:40'],
            'link'       => ['nullable', 'string', 'max:255'],
            // Not on the form any more — the model hands the colour out on create.
            // Kept nullable rather than dropped so a seeder or a test may still
            // set one deliberately; "required" here would reject every save.
            'tone'       => ['nullable', Rule::in(Event::TONES)],
            'image'      => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['nullable', 'boolean'],
            'show_home'  => ['nullable', 'boolean'],

            /* ---- Details page (/events/{slug}) ---- */
            'slug'                => ['nullable', 'string', 'max:200', 'alpha_dash', Rule::unique('events', 'slug')->ignore($id)],
            'short_description'   => ['nullable', 'string', 'max:600'],
            'description'         => ['nullable', 'string', 'max:20000'],
            'organizer'           => ['nullable', 'string', 'max:150'],
            'end_time'            => ['nullable', 'date_format:H:i'],
            'venue'               => ['nullable', 'string', 'max:255'],
            'city'                => ['nullable', 'string', 'max:120'],
            'state'               => ['nullable', 'string', 'max:120'],
            'country'             => ['nullable', 'string', 'max:120'],
            'offer_price'         => ['nullable', 'string', 'max:40'],
            'discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'total_seats'         => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'available_seats'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'duration'            => ['nullable', 'string', 'max:80'],
            'language'            => ['nullable', 'string', 'max:80'],
            'level'               => ['nullable', 'string', 'max:80'],
            'is_featured'         => ['nullable', 'boolean'],
            'banner_image'        => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
            'thumbnail'           => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],

            /* ---- SEO (falls back to Settings → SEO Defaults when blank) ---- */
            'seo_title'       => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'seo_keywords'    => ['nullable', 'string', 'max:255'],
            'og_image'        => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'schema_json'     => ['nullable', 'string', 'max:20000', 'json'],

            /* ---- Repeaters. Rows with no title/name/question are dropped in the
                    controller, so every field here stays nullable. ---- */
            'highlights'               => ['nullable', 'array', 'max:12'],
            'highlights.*.icon'        => ['nullable', 'string', Rule::in(array_keys(Event::HIGHLIGHT_ICONS))],
            'highlights.*.title'       => ['nullable', 'string', 'max:150'],
            'highlights.*.description' => ['nullable', 'string', 'max:400'],
            'highlights.*.is_active'   => ['nullable', 'boolean'],

            'speakers'                  => ['nullable', 'array', 'max:12'],
            'speakers.*.name'           => ['nullable', 'string', 'max:150'],
            'speakers.*.designation'    => ['nullable', 'string', 'max:150'],
            'speakers.*.company'        => ['nullable', 'string', 'max:150'],
            'speakers.*.linkedin'       => ['nullable', 'string', 'max:255'],
            'speakers.*.photo'          => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'speakers.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'speakers.*.is_active'      => ['nullable', 'boolean'],

            'event_faqs'             => ['nullable', 'array', 'max:12'],
            'event_faqs.*.question'  => ['nullable', 'string', 'max:255'],
            'event_faqs.*.answer'    => ['nullable', 'string', 'max:2000'],
            'event_faqs.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'show_home'  => $this->boolean('show_home'),
            'sort_order' => $this->input('sort_order', 0),
            'type'       => $this->input('type') ?: Event::TYPES[0],
            // Empty date/time inputs post "" — store them as NULL so the card
            // treats them as "not scheduled" rather than an invalid value.
            'event_date' => $this->input('event_date') ?: null,
            // <input type="time"> can post "10:00:00" in some browsers; the rule
            // expects H:i, so trim the seconds before validating.
            'event_time' => $this->normalisedTime('event_time'),
            'end_time'   => $this->normalisedTime('end_time'),
            'location'   => trim((string) $this->input('location')) ?: null,

            'is_featured' => $this->boolean('is_featured'),
            // The admin may leave the slug blank; the model then derives it from
            // the title. "" would fail alpha_dash, so send NULL instead.
            'slug'        => Str::slug((string) $this->input('slug')) ?: null,
            'schema_json' => trim((string) $this->input('schema_json')) ?: null,

            'highlights' => $this->cleanedRows('highlights', 'title'),
            'speakers'   => $this->cleanedRows('speakers', 'name'),
            'event_faqs' => $this->cleanedRows('event_faqs', 'question'),
        ]);
    }

    /**
     * Repeater rows arrive with a blank template row whenever the admin clicks
     * "Add" and then saves without typing, so drop any row whose key field is
     * empty before validation — otherwise max:12 counts rows that will never be
     * stored. Keys are re-indexed so the display order matches the form order.
     */
    private function cleanedRows(string $key, string $required): array
    {
        $rows = $this->input($key);

        if (! is_array($rows)) {
            return [];
        }

        $kept = array_filter($rows, fn ($row) => is_array($row) && filled($row[$required] ?? null));

        // Files live in a parallel bag, so re-indexing here would break the
        // speakers.*.photo pairing. Keep the original keys and let the
        // controller walk them in order.
        return $kept;
    }

    /** "10:00:00" / "10:00" -> "10:00"; anything blank or unparseable -> null. */
    private function normalisedTime(string $field): ?string
    {
        $time = trim((string) $this->input($field));

        if ($time === '') {
            return null;
        }

        return preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)
            ? sprintf('%02d:%02d', $m[1], $m[2])
            : $time;   // leave it for the validator to reject
    }

    public function messages(): array
    {
        return [
            'image.required'        => 'Please upload a photo of the speaker.',
            'image.image'           => 'The photo must be an image (WebP, PNG or JPG).',
            'image.max'             => 'The photo may not be larger than 2 MB.',
            'type.in'               => 'Choose one of the listed event types.',
            'event_date.date'       => 'Enter a valid event date.',
            'event_time.date_format' => 'Enter the time as HH:MM (for example 10:00).',
            'end_time.date_format'  => 'Enter the end time as HH:MM (for example 18:00).',
            'slug.unique'           => 'Another event already uses that URL slug.',
            'schema_json.json'      => 'The schema markup must be valid JSON.',
            'banner_image.max'      => 'The banner may not be larger than 3 MB.',
        ];
    }
}
