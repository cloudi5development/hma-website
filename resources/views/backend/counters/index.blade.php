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


    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search counter label"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Value</th><th>Label</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($counters as $counter)
                        <tr>
                            <td class="hm-table__name">{{ $counter->number }}</td>
                            <td>{{ $counter->label }}</td>
                            <td>
                                <span class="flag-text">
                                    {{ collect([$counter->show_home ? "Home" : null, $counter->show_about ? "About" : null, $counter->show_testimonials ? "Testimonials" : null])->filter()->implode(" · ") ?: "—" }}
                                </span>
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
                                          data-confirm="Delete this counter?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 hm-table__sub">No counters yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $counters])

@endsection
