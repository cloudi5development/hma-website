@extends('backend.template.layouts.template-base')

@section('title', 'Enquiry from ' . $enquiry->name)
@section('page_title', 'Course Enquiry')
@section('page_sub', 'Course Enquiries')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.course-enquiries.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Enquiries
        </a>
    </div>


    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">{{ $enquiry->name }}</h2></div>
                <div class="hm-card__body">
                    <table class="hm-table">
                        <tbody>
                            <tr><td style="width:180px;color:#6b6357;">Course</td><td>{{ $enquiry->course_name }} @if (! $enquiry->course) <span class="hm-table__sub">(course removed)</span> @endif</td></tr>
                            {{-- Only set when the enquiry came from an Apply button
                                 on a schedule row, so the row is left out otherwise. --}}
                            @if ($enquiry->batch)
                                <tr><td style="color:#6b6357;">Batch</td><td>{{ $enquiry->batch }}</td></tr>
                            @endif
                            <tr><td style="color:#6b6357;">Email</td><td><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></td></tr>
                            <tr><td style="color:#6b6357;">Mobile</td><td><a href="tel:{{ $enquiry->phone }}">{{ $enquiry->phone }}</a></td></tr>
                            <tr><td style="color:#6b6357;">City</td><td>{{ $enquiry->city ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Career goal</td><td>{{ $enquiry->career_goal ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">IP address</td><td>{{ $enquiry->ip_address ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Received</td><td>{{ $enquiry->created_at->format('d M Y, g:i a') }}</td></tr>
                        </tbody>
                    </table>

                    <div class="form-row mt-3" style="margin-bottom:0">
                        <label class="form-label">Message</label>
                        <p style="white-space:pre-wrap;color:#23180f;margin:0;">{{ $enquiry->message ?: '— no message —' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="hm-card mb-3">
                <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                <div class="hm-card__body">
                    <form method="POST" action="{{ route('backend.course-enquiries.status', $enquiry) }}">
                        @csrf @method('PATCH')
                        <select name="status" class="form-control-hm mb-2">
                            @foreach (\App\Models\CourseEnquiry::STATUSES as $s)
                                <option value="{{ $s }}" {{ $enquiry->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-brand" style="width:100%;justify-content:center">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="hm-card">
                <div class="hm-card__body d-flex flex-column gap-2">
                    <a href="mailto:{{ $enquiry->email }}" class="btn-ghost" style="justify-content:center">Reply by Email</a>
                    <form method="POST" action="{{ route('backend.course-enquiries.destroy', $enquiry) }}"
                          data-confirm="Delete this enquiry?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-soft" style="width:100%;justify-content:center">Delete Enquiry</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
