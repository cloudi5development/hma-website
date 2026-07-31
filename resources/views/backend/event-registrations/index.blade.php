@extends('backend.template.layouts.template-base')

@section('title', 'Event Registrations')
@section('page_title', 'Event Registrations')
@section('page_sub', 'Registrations submitted from the event details pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Event Registrations</h1>
            <p class="page-head__sub">
                {{ $registrations->total() }} registration{{ $registrations->total() === 1 ? '' : 's' }}
                @if ($event) · for <strong>{{ $event->title }}</strong> @endif
            </p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="{{ route('backend.event-registrations.export-excel', request()->query()) }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                Export Excel
            </a>
            <a href="{{ route('backend.event-registrations.export', request()->query()) }}" class="btn-ghost">Export CSV</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <form method="GET" class="row g-2 align-items-end">
                @if ($event) <input type="hidden" name="event" value="{{ $event->id }}"> @endif
                <div class="col-12 col-md-4">
                    <label class="form-label" for="date">Date</label>
                    <input type="date" id="date" name="date" value="{{ request('date') }}" class="form-control-hm">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn-brand" style="flex:1;justify-content:center">Filter</button>
                    <a href="{{ route('backend.event-registrations.index') }}" class="btn-ghost">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="hm-card">
        @include('backend.partials.table-toolbar', [
            'placeholder' => 'Search name, email, mobile or event',
            'statuses'    => collect($statuses)->mapWithKeys(fn ($s) => [$s => $s])->all(),
        ])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Event</th><th>Name</th><th>Email</th><th>Mobile</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        <tr>
                            <td>#{{ $registration->id }}</td>
                            <td class="hm-table__name">{{ $registration->event_title }}</td>
                            <td>{{ $registration->name }}</td>
                            <td><a href="mailto:{{ $registration->email }}" class="hm-table__sub">{{ $registration->email }}</a></td>
                            <td>{{ $registration->phone }}</td>
                            <td class="hm-table__date">
                                {{ $registration->created_at->format('d M Y') }}
                                <span class="hm-table__sub">{{ $registration->created_at->format('g:i a') }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('backend.event-registrations.status', $registration) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <select name="status" class="pill pill--tiny pill--{{ strtolower($registration->status) }} hm-status-select" onchange="this.form.submit()"
                                            style="border:0;cursor:pointer;">
                                        @foreach ($statuses as $s)
                                            <option value="{{ $s }}" {{ $registration->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.event-registrations.show', $registration) }}" class="btn-ghost btn-icon" aria-label="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.event-registrations.destroy', $registration) }}"
                                          data-confirm="Delete this registration?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 hm-table__sub">No event registrations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $registrations])

@endsection
