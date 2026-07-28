@extends('backend.template.layouts.template-base')

@section('title', 'Course Enquiries')
@section('page_title', 'Course Enquiries')
@section('page_sub', 'Enquiries submitted from the course pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Course Enquiries</h1>
            <p class="page-head__sub">
                {{ $enquiries->total() }} enquir{{ $enquiries->total() === 1 ? 'y' : 'ies' }}
                @if ($course) · for <strong>{{ $course->name }}</strong> @endif
            </p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="{{ route('backend.course-enquiries.export-excel', request()->query()) }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
            <a href="{{ route('backend.course-enquiries.export', request()->query()) }}" class="btn-ghost">Export CSV</a>
        </div>
    </div>


    {{-- Filters --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <form method="GET" class="row g-2 align-items-end">
                @if ($course) <input type="hidden" name="course" value="{{ $course->id }}"> @endif
                <div class="col-12 col-md-4">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control-hm">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn-brand" style="flex:1;justify-content:center">Filter</button>
                    <a href="{{ route('backend.course-enquiries.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="hm-card">
        @include("backend.partials.table-toolbar", [
            "placeholder" => "Search name, email, mobile or course",
            "statuses"    => collect($statuses)->mapWithKeys(fn ($s) => [$s => $s])->all(),
        ])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Course</th><th>Student</th><th>Email</th><th>Mobile</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enquiries as $enquiry)
                        <tr>
                            <td>#{{ $enquiry->id }}</td>
                            <td class="hm-table__name">{{ $enquiry->course_name }}</td>
                            <td>{{ $enquiry->name }}</td>
                            <td><a href="mailto:{{ $enquiry->email }}" class="hm-table__sub">{{ $enquiry->email }}</a></td>
                            <td>{{ $enquiry->phone }}</td>
                            <td class="hm-table__date">
                                {{ $enquiry->created_at->format('d M Y') }}
                                <span class="hm-table__sub">{{ $enquiry->created_at->format('g:i a') }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('backend.course-enquiries.status', $enquiry) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="status" class="pill pill--tiny pill--{{ strtolower($enquiry->status) }} hm-status-select" onchange="this.form.submit()"
                                            style="border:0;cursor:pointer;">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s }}" {{ $enquiry->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.course-enquiries.show', $enquiry) }}" class="btn-ghost btn-icon" aria-label="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.course-enquiries.destroy', $enquiry) }}"
                                          data-confirm="Delete this enquiry?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 hm-table__sub">No course enquiries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $enquiries])

@endsection
