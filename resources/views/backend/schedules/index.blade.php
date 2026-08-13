@extends('backend.template.layouts.template-base')

@section('title', 'Course Schedule')
@section('page_title', 'Course Schedule')
@section('page_sub', 'Upcoming batches shown on the home page and /schedules')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Course Schedule</h1>
            <p class="page-head__sub">
                {{ $schedules->total() }} batch{{ $schedules->total() === 1 ? '' : 'es' }} ·
                {{ $upcoming }} showing on the website
                @if ($courseFilter)
                    · filtered to <strong>{{ $courseFilter->name }}</strong>
                    <a href="{{ route('backend.schedules.index') }}" class="hm-table__sub">(clear)</a>
                @endif
            </p>
        </div>
        <a href="{{ route('backend.schedules.create', $courseFilter ? ['course' => $courseFilter->id] : []) }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Schedule
        </a>
    </div>

    <div class="hm-card">
        @include('backend.partials.table-toolbar', ['placeholder' => 'Search course name'])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Course</th><th>Category</th><th>Start Date</th><th>End Date</th>
                        <th>Duration</th><th>Timing</th><th>Fee</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            $course = $schedule->course;
                            // A batch that has already started is kept and listed,
                            // but the website leaves it out — say so here rather
                            // than letting it look live.
                            $past = $schedule->start_date->isPast();
                        @endphp
                        <tr>
                            <td class="hm-table__name">
                                {{ $course?->name ?? '—' }}
                                @if ($course && ! $course->is_active)
                                    <span class="hm-table__sub d-block">Course is hidden</span>
                                @endif
                            </td>
                            <td>
                                @if ($course?->category)
                                    <span class="pill pill--tiny">{{ $course->category->name }}</span>
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $schedule->start_date_label }}
                                <span class="hm-table__sub d-block">{{ $schedule->start_day_label }}</span>
                            </td>
                            <td>
                                @if ($schedule->end_date_label)
                                    {{ $schedule->end_date_label }}
                                    <span class="hm-table__sub d-block">{{ $schedule->end_day_label }}</span>
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                            <td>{{ $schedule->duration ?: $course?->duration ?: '—' }}</td>
                            <td>{{ $schedule->time_range_label ?: '—' }}</td>
                            <td>
                                @if ($schedule->fee_label)
                                    {{ $schedule->fee_label }}
                                @else
                                    <span class="hm-table__sub">Contact for Fee</span>
                                @endif
                            </td>
                            <td>
                                @if (! $schedule->is_active)
                                    <span class="pill pill--tiny pill--inactive">Hidden</span>
                                @elseif ($past)
                                    <span class="pill pill--tiny pill--inactive">Started</span>
                                @else
                                    <span class="pill pill--tiny pill--active">Active</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.schedules.edit', $schedule) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.schedules.destroy', $schedule) }}"
                                          data-confirm="Delete this batch?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5 hm-table__sub">No batches yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $schedules])

@endsection
