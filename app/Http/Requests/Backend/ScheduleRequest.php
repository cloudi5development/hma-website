<?php

namespace App\Http\Requests\Backend;

use App\Models\CourseSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind admin.auth + admin.module.
        return true;
    }

    public function rules(): array
    {
        return [
            // The category is the form's course filter, not a stored column —
            // the batch belongs to the course. Validated anyway so a tampered
            // post cannot smuggle a value through to the view on a re-render.
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'course_id'   => ['required', 'integer', 'exists:courses,id'],

            'start_date'  => ['required', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'duration'    => ['nullable', 'string', 'max:60'],

            // Required, unlike the other optional batch fields: the mode moved
            // here off the course, so this is now the only place the website can
            // learn whether a course is taught online, on campus or both.
            'training_mode' => ['required', Rule::in(CourseSchedule::TRAINING_MODES)],

            // Optional, and independent of each other: a batch may advertise a
            // start time with no finish. The pair is only compared when both are
            // given AND the batch runs within one day — an overnight or
            // multi-day batch legitimately ends "before" it starts on the clock,
            // so that check lives in withValidator rather than here.
            'start_time'  => ['nullable', 'date_format:H:i'],
            'end_time'    => ['nullable', 'date_format:H:i'],

            // Nullable, never 0: "no fee entered" and "this batch is free" are
            // different things, and the site must not print ₹0 for either.
            'fee'         => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'show_fee'    => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start_time');
            $end   = $this->input('end_time');

            // Only meaningful for a batch that starts and ends on the same day.
            $sameDay = blank($this->input('end_date'))
                || $this->input('end_date') === $this->input('start_date');

            if ($start && $end && $sameDay && $end <= $start) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_fee'  => $this->boolean('show_fee'),
            'is_active' => $this->boolean('is_active'),
            // An untouched <input type="time"> posts "", which `date_format`
            // would reject. Blank means "no time given", so normalise it here.
            'start_time' => $this->filled('start_time') ? $this->input('start_time') : null,
            'end_time'   => $this->filled('end_time') ? $this->input('end_time') : null,
            'fee'        => $this->filled('fee') ? $this->input('fee') : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'course_id.required'       => 'Please choose the course this batch is for.',
            'start_date.required'      => 'Please set the batch start date.',
            'end_date.after_or_equal'  => 'The end date must fall on or after the start date.',
            'training_mode.required'   => 'Please choose how this batch is taught.',
            'training_mode.in'         => 'Choose one of: ' . implode(', ', CourseSchedule::TRAINING_MODES) . '.',
            'start_time.date_format'   => 'Enter the start time as a time, e.g. 10:00.',
            'end_time.date_format'     => 'Enter the end time as a time, e.g. 13:30.',
            'fee.numeric'              => 'Enter the fee as a number, without the ₹ sign.',
        ];
    }
}
