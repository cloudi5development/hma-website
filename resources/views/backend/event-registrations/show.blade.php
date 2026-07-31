@extends('backend.template.layouts.template-base')

@section('title', 'Registration from ' . $registration->name)
@section('page_title', 'Event Registration')
@section('page_sub', 'Event Registrations')

@section('content')

    <div class="page-head">
        <a href="{{ route('backend.event-registrations.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Registrations
        </a>
        @if ($registration->event)
            <a href="{{ route('frontend.event-details', $registration->event->slug) }}" class="btn-ghost" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8.5 7H17v8.5"/></svg>
                View Event
            </a>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="hm-card">
                <div class="hm-card__head"><h2 class="hm-card__title">{{ $registration->name }}</h2></div>
                <div class="hm-card__body">
                    <table class="hm-table">
                        <tbody>
                            {{-- event_title is the snapshot taken at registration time, so
                                 it still reads correctly once the event is gone. --}}
                            <tr><td style="width:180px;color:#6b6357;">Event</td><td>{{ $registration->event_title }} @if (! $registration->event) <span class="hm-table__sub">(event removed)</span> @endif</td></tr>
                            <tr><td style="color:#6b6357;">Email</td><td><a href="mailto:{{ $registration->email }}">{{ $registration->email }}</a></td></tr>
                            <tr><td style="color:#6b6357;">Mobile</td><td><a href="tel:{{ $registration->phone }}">{{ $registration->phone }}</a></td></tr>
                            <tr><td style="color:#6b6357;">City</td><td>{{ $registration->city ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Professional status</td><td>{{ $registration->professional_status ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Company / College</td><td>{{ $registration->organisation ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Terms accepted</td><td>{{ $registration->agreed_terms ? 'Yes' : 'No' }}</td></tr>
                            <tr><td style="color:#6b6357;">IP address</td><td>{{ $registration->ip_address ?: '—' }}</td></tr>
                            <tr><td style="color:#6b6357;">Received</td><td>{{ $registration->created_at->format('d M Y, g:i a') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="hm-card mb-3">
                <div class="hm-card__head"><h2 class="hm-card__title">Status</h2></div>
                <div class="hm-card__body">
                    <form method="POST" action="{{ route('backend.event-registrations.status', $registration) }}">
                        @csrf @method('PATCH')
                        <select name="status" class="form-control-hm mb-2">
                            @foreach (\App\Models\EventRegistration::STATUSES as $s)
                                <option value="{{ $s }}" {{ $registration->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-brand" style="width:100%;justify-content:center">Update Status</button>
                    </form>
                </div>
            </div>

            <div class="hm-card">
                <div class="hm-card__body d-flex flex-column gap-2">
                    <a href="mailto:{{ $registration->email }}" class="btn-ghost" style="justify-content:center">Reply by Email</a>
                    <form method="POST" action="{{ route('backend.event-registrations.destroy', $registration) }}"
                          data-confirm="Delete this registration?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger-soft" style="width:100%;justify-content:center">Delete Registration</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
