@extends('backend.template.layouts.template-base')

@section('title', 'Testimonials')
@section('page_title', 'Testimonials')
@section('page_sub', 'Learner reviews shown on the home, about and testimonials pages')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Testimonials</h1>
            <p class="page-head__sub">{{ $testimonials->total() }} testimonial{{ $testimonials->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.testimonials.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Testimonial
        </a>
    </div>


    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search name or company"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Photo</th><th>Name</th><th>Role</th><th>Rating</th><th>Visible On</th><th>Status</th><th class="text-end">Actions</th>
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
                            <td>
                                <span class="flag-text">
                                    {{ collect([$testimonial->show_home ? "Home" : null, $testimonial->show_about ? "About" : null, $testimonial->show_testimonials ? "Testimonials" : null])->filter()->implode(" · ") ?: "—" }}
                                </span>
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
                                          data-confirm="Delete this testimonial?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 hm-table__sub">No testimonials yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $testimonials])

@endsection
