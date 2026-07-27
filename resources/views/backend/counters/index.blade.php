@extends('backend.template.layouts.template-base')

@section('title', 'Counters')
@section('page_title', 'Counters')
@section('page_sub', 'The statistics strip shown on the home, about and testimonials pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Counters</h1>
            <p class="page-head__sub">{{ $counters->total() }} counter{{ $counters->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.counters.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Counter
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
                        <th>Value</th><th>Label</th><th>Order</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($counters as $counter)
                        <tr>
                            <td class="hm-table__name">{{ $counter->number }}</td>
                            <td>{{ $counter->label }}</td>
                            <td>{{ $counter->sort_order }}</td>
                            <td>
                                @if ($counter->show_home)         <span class="pill pill--interested pill--tiny">Home</span> @endif
                                @if ($counter->show_about)        <span class="pill pill--interested pill--tiny">About</span> @endif
                                @if ($counter->show_testimonials) <span class="pill pill--interested pill--tiny">Testimonials</span> @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $counter->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $counter->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.counters.edit', $counter) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.counters.destroy', $counter) }}"
                                          onsubmit="return confirm('Delete this counter?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No counters yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $counters])

@endsection
