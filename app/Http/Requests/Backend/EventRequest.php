<?php

namespace App\Http\Requests\Backend;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
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

        return [
            'speaker'    => ['required', 'string', 'max:120'],
            'title'      => ['required', 'string', 'max:160'],
            // Schedule — all optional; the card leaves out whichever row is blank.
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'location'   => ['nullable', 'string', 'max:255'],
            'type'       => ['required', 'string', 'max:60'],
            'price'      => ['nullable', 'string', 'max:40'],
            'link'       => ['nullable', 'string', 'max:255'],
            'tone'       => ['required', Rule::in(Event::TONES)],
            'image'      => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'  => ['nullable', 'boolean'],
            'show_home'  => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'show_home'  => $this->boolean('show_home'),
            'sort_order' => $this->input('sort_order', 0),
            'type'       => $this->input('type') ?: 'Live Event',
            // Empty date/time inputs post "" — store them as NULL so the card
            // treats them as "not scheduled" rather than an invalid value.
            'event_date' => $this->input('event_date') ?: null,
            // <input type="time"> can post "10:00:00" in some browsers; the rule
            // expects H:i, so trim the seconds before validating.
            'event_time' => $this->normalisedTime(),
            'location'   => trim((string) $this->input('location')) ?: null,
        ]);
    }

    /** "10:00:00" / "10:00" -> "10:00"; anything blank or unparseable -> null. */
    private function normalisedTime(): ?string
    {
        $time = trim((string) $this->input('event_time'));

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
            'event_date.date'       => 'Enter a valid event date.',
            'event_time.date_format' => 'Enter the time as HH:MM (for example 10:00).',
        ];
    }
}
