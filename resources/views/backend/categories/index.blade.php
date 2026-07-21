@extends('backend.template.layouts.template-base')

@section('title', 'Categories')
@section('page_title', 'Categories')
@section('page_sub', 'Grouped under departments; drive the home grid, mega-menu and course filters')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Categories</h1>
            <p class="page-head__sub">{{ $categories->count() }} categor{{ $categories->count() === 1 ? 'y' : 'ies' }}</p>
        </div>
        <a href="{{ route('backend.categories.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Category
        </a>
    </div>

    @include('backend.partials.flash')

    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Icon</th><th>Category</th><th>Department</th><th>Courses</th><th>Order</th><th>Home</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><span class="tbl-logo"><img src="{{ $category->icon_url }}" alt="{{ $category->name }}"></span></td>
                            <td class="hm-table__name">{{ $category->name }}</td>
                            <td>{{ $category->department?->name }}</td>
                            <td>{{ $category->courses_count }}</td>
                            <td>{{ $category->sort_order }}</td>
                            <td>
                                @if ($category->show_home) <span class="pill pill--interested pill--tiny">On Home</span> @endif
                                @if ($category->is_featured) <span class="pill pill--interested pill--tiny">Featured</span> @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $category->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $category->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.categories.edit', $category) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete this category?');" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 hm-table__sub">No categories yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
