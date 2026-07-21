@extends('backend.template.layouts.template-base')

@section('title', 'Testimonials')
@section('page_title', 'Testimonials')
@section('page_sub', 'Learner reviews shown on the home, about and testimonials pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Testimonials</h1>
            <p class="page-head__sub">{{ $testimonials->count() }} testimonial{{ $testimonials->count() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.testimonials.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Testimonial
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
                        <th>Photo</th><th>Name</th><th>Role</th><th>Rating</th><th>Order</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($testimonials as $testimonial)
                        <tr>
                            <td><span class="tbl-logo tbl-logo--round"><img src="{{ $testimonial->photo_url }}" alt="{{ $testimonial->name }}"></span></td>
                            <td class="hm-table__name">{{ $testimonial->name }}</td>
                            <td>{{ $testimonial->role }}{{ $testimonial->company ? ' · '.$testimonial->company : '' }}</td>
                            <td class="hm-stars" aria-label="{{ $testimonial->rating }} out of 5">
                                {{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}
                            </td>
                            <td>{{ $testimonial->sort_order }}</td>
                            <td>
                                @if ($testimonial->show_home)         <span class="pill pill--interested pill--tiny">Home</span> @endif
                                @if ($testimonial->show_about)        <span class="pill pill--interested pill--tiny">About</span> @endif
                                @if ($testimonial->show_testimonials) <span class="pill pill--interested pill--tiny">Testimonials</span> @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $testimonial->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $testimonial->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.testimonials.edit', $testimonial) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.testimonials.destroy', $testimonial) }}"
                                          onsubmit="return confirm('Delete this testimonial?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 hm-table__sub">No testimonials yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
