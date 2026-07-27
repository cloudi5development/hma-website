@extends('backend.template.layouts.template-base')

@section('title', 'Trusted Partners')
@section('page_title', 'Trusted Partners')
@section('page_sub', 'Logos shown in the marquee on the home, about and testimonials pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Trusted Partners</h1>
            <p class="page-head__sub">{{ $partners->total() }} partner{{ $partners->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.partners.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Partner
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
                        <th>Logo</th><th>Name</th><th>Order</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $partner)
                        <tr>
                            <td><span class="tbl-logo"><img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}"></span></td>
                            <td class="hm-table__name">{{ $partner->name }}</td>
                            <td>{{ $partner->sort_order }}</td>
                            <td>
                                @if ($partner->show_home)         <span class="pill pill--interested pill--tiny">Home</span> @endif
                                @if ($partner->show_about)        <span class="pill pill--interested pill--tiny">About</span> @endif
                                @if ($partner->show_testimonials) <span class="pill pill--interested pill--tiny">Testimonials</span> @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $partner->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $partner->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.partners.edit', $partner) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.partners.destroy', $partner) }}"
                                          onsubmit="return confirm('Delete this partner?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No partners yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $partners])

@endsection
