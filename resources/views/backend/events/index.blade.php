@extends('backend.template.layouts.template-base')

@section('title', 'Upcoming Events')
@section('page_title', 'Upcoming Events')
@section('page_sub', 'Events shown in the cover-flow carousel on the home page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Upcoming Events</h1>
            <p class="page-head__sub">{{ $events->total() }} event{{ $events->total() === 1 ? '' : 's' }} · 3 show at a time, the rest rotate in</p>
        </div>
        <a href="{{ route('backend.events.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Event
        </a>
    </div>

    @if (session('success'))
        <div class="alert-hm alert-hm--success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Photo</th><th>Speaker</th><th>Title</th><th>Type</th><th>Price</th><th>Tone</th><th>Order</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td><span class="tbl-logo tbl-logo--round"><img src="{{ $event->image_url }}" alt="{{ $event->speaker }}"></span></td>
                            <td class="hm-table__name">{{ $event->speaker }}</td>
                            <td>{{ $event->title }}</td>
                            <td>{{ $event->type }}</td>
                            <td>{{ $event->price }}</td>
                            <td><span class="pill pill--tiny pill--interested">{{ ucfirst($event->tone) }}</span></td>
                            <td>{{ $event->sort_order }}</td>
                            <td>
                                <span class="pill pill--tiny {{ $event->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $event->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.events.edit', $event) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.events.destroy', $event) }}"
                                          onsubmit="return confirm('Delete this event?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-5 hm-table__sub">No events yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $events])

@endsection
