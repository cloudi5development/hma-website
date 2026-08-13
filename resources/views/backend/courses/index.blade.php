@extends('backend.template.layouts.template-base')

@section('title', 'Courses')
@section('page_title', 'Courses')
@section('page_sub', 'Every training program, its category, flags and SEO')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Courses</h1>
            <p class="page-head__sub">{{ $courses->total() }} course{{ $courses->total() === 1 ? '' : 's' }} · {{ $popularCount }} in the home slider</p>
        </div>
        <a href="{{ route('backend.courses.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Course
        </a>
    </div>

    @include('backend.partials.flash')

    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search course name"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Image</th><th>Course</th><th>Category</th><th>Flags</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td><span class="tbl-logo"><img src="{{ $course->image_url ?? asset('backend/template/images/actions/product-img.svg') }}" alt=""></span></td>
                            <td class="hm-table__name">{{ $course->name }}</td>
                            <td>{{ $course->category?->name }}<br><span class="hm-table__sub">{{ $course->category?->department?->name }}</span></td>
                            <td>
                                <span class="flag-text">
                                    {{ collect([$course->is_popular ? "Popular" : null, $course->is_featured ? "Featured" : null, $course->is_continue_learning ? "Continue" : null])->filter()->implode(" · ") ?: "—" }}
                                </span>
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $course->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $course->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('frontend.course-details', $course->slug) }}" target="_blank" rel="noopener" class="btn-ghost btn-icon" aria-label="View">
                                        <span class="act-ico act-ico--view" aria-hidden="true"></span>
                                    </a>
                                    <a href="{{ route('backend.course-enquiries.index', ['course' => $course->id]) }}" class="btn-ghost" aria-label="View enquiries for this course" title="View Enquiries">
                                        Enquiries
                                    </a>
                                    <a href="{{ route('backend.courses.edit', $course) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.courses.destroy', $course) }}"
                                          data-confirm="Delete this course?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No courses yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $courses])

@endsection
