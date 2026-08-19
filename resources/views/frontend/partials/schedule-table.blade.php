{{--
|--------------------------------------------------------------------------
| Upcoming course schedules table (shared component)
|--------------------------------------------------------------------------
|
| Used by the home page's "Upcoming Course Schedules" section (first four
| batches) and by the /schedules page (all of them). Render it with a
| collection of CourseSchedule rows, each with its course and category loaded:
|
|     @include('frontend.partials.schedule-table', ['schedules' => $schedules])
|
| and push its stylesheet from the page:
|
|     <link rel="stylesheet" href="{{ asset('assets/css/frontend/schedules.css') }}">
|
| The Apply buttons open the shared enquiry modal, so the page must also
| @include('frontend.partials.course-enquiry-modal') and load Bootstrap's JS.
|
| $animate is optional — the home page passes true for the row-by-row reveal
| that the [data-io] observer in home.css drives; other pages leave it off.
|
| A real <table> so the dates stay readable to assistive tech; under 992px the
| same rows restyle into stacked cards, each cell labelled from its data-label.
--}}
@php
    $animate ??= false;
@endphp

<div class="hm-sched__panel">
    <table class="hm-sched__table hm-sched__table--wide">
        <caption class="visually-hidden">Upcoming course batches, soonest first</caption>
        <thead>
            <tr>
                <th scope="col">Course</th>
                <th scope="col">Category</th>
                <th scope="col">Start Date</th>
                <th scope="col">End Date</th>
                <th scope="col">Duration</th>
                <th scope="col">Fee</th>
                <th scope="col"><span class="visually-hidden">Action</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedules as $i => $schedule)
                @php
                    $course = $schedule->course;

                    // What the modal shows and the enquiry records — a range when
                    // the batch has an end date, the start alone when it does not.
                    $batchLabel = $schedule->end_date_label
                        ? $schedule->start_date_label . ' – ' . $schedule->end_date_label
                        : $schedule->start_date_label;

                    // The batch's own duration, falling back to the course's, so
                    // the column is never blank when the batch left it out.
                    $duration = $schedule->duration ?: $course->duration;
                @endphp
                {{-- data-category / data-month are what the /schedules toolbar
                     filters on. Inert on the home page, which has no toolbar. --}}
                <tr @class(['hm-sched__row', 'hm-anim hm-anim--up hm-anim--d' . min($i + 3, 8) => $animate])
                    data-category="{{ $course->category?->slug }}"
                    data-month="{{ $schedule->start_date->format('Y-m') }}">
                    <td class="hm-sched__course" data-label="Course">
                        <span class="hm-sched__thumb" aria-hidden="true">
                            @if ($course->image_url)
                                <img src="{{ $course->image_url }}" alt="" loading="lazy" decoding="async">
                            @endif
                        </span>
                        <span class="hm-sched__course-name">{{ $course->name }}</span>
                    </td>

                    <td data-label="Category">
                        @if ($course->badge)
                            <span class="hm-course__badge">{{ $course->badge }}</span>
                        @else
                            <span class="hm-sched__none">—</span>
                        @endif
                    </td>

                    {{-- The times are the batch's DAILY timing ("10:00 AM – 01:30 PM"),
                         not the clock time of the first and last day, so the range
                         stays together as one line rather than being split across
                         the two date cells. Optional, so the cell only grows the
                         extra line when a time was entered. --}}
                    <td data-label="Start Date">
                        <span class="hm-sched__stack">
                            <span class="hm-sched__stack-main">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                {{ $schedule->start_date_label }}
                            </span>
                            <span class="hm-sched__stack-sub">{{ $schedule->start_day_label }}</span>
                            @if ($schedule->time_range_label)
                                <span class="hm-sched__stack-time">
                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    {{ $schedule->time_range_label }}
                                </span>
                            @endif
                        </span>
                    </td>

                    <td data-label="End Date">
                        @if ($schedule->end_date_label)
                            <span class="hm-sched__stack">
                                <span class="hm-sched__stack-main">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $schedule->end_date_label }}
                                </span>
                                <span class="hm-sched__stack-sub">{{ $schedule->end_day_label }}</span>
                            </span>
                        @else
                            <span class="hm-sched__none">—</span>
                        @endif
                    </td>

                    {{-- The mode sits under the duration rather than in a column of
                         its own: this table is already seven columns wide, and the
                         stack's sub line is exactly the slot for a short qualifier.
                         Optional, so a batch entered before the field moved off the
                         course simply shows the duration alone. --}}
                    <td data-label="Duration">
                        @if ($duration || $schedule->training_mode)
                            <span class="hm-sched__stack">
                                @if ($duration)
                                    <span class="hm-sched__stack-main">
                                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                        {{ $duration }}
                                    </span>
                                @endif
                                @if ($schedule->training_mode)
                                    <span class="hm-sched__stack-sub">{{ $schedule->training_mode }}</span>
                                @endif
                            </span>
                        @else
                            <span class="hm-sched__none">—</span>
                        @endif
                    </td>

                    {{-- "Show Fee" off, or no fee entered, reads as an invitation
                         to ask — never ₹0 or an empty cell. --}}
                    <td data-label="Fee">
                        @if ($schedule->fee_label)
                            <span class="hm-sched__fee">{{ $schedule->fee_label }}</span>
                        @else
                            <span class="hm-sched__fee hm-sched__fee--ask">Contact for Fee</span>
                        @endif
                    </td>

                    <td class="hm-sched__action">
                        <span class="hm-sched__actions">
                            {{-- The eye opens the existing course details page; the
                                 label is on the link, not in the icon, so it is
                                 announced and reachable by keyboard. --}}
                            <a class="hm-sched__eye" href="{{ route('frontend.course-details', $course->slug) }}"
                               aria-label="View the {{ $course->name }} course"
                               title="View course">
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                            </a>

                            {{-- Opens the shared enquiry modal with this course
                                 preselected and this batch named on the form. --}}
                            <button class="hm-course__btn hm-sched__btn" type="button"
                                    data-bs-toggle="modal" data-bs-target="#hmEnquireModal"
                                    data-enq-course="{{ $course->id }}"
                                    data-enq-batch="{{ $batchLabel }}"
                                    aria-label="Apply for the {{ $course->name }} batch starting {{ $schedule->start_date_label }}">
                                <span>Apply</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
