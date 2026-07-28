@extends('backend.template.layouts.template-base')

@section('title', 'Blog')
@section('page_title', 'Blog')
@section('page_sub', 'Posts shown on the blog page, blog details, and the home Latest Blog grid')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Blog</h1>
            <p class="page-head__sub">{{ $blogs->total() }} post{{ $blogs->total() === 1 ? '' : 's' }}</p>
        </div>
        <a href="{{ route('backend.blogs.create') }}" class="btn-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add Post
        </a>
    </div>


    <div class="hm-card">
        @include("backend.partials.table-toolbar", ["placeholder" => "Search blog title"])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Cover</th><th>Title</th><th>Category</th><th>Date</th><th>Shows On</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blogs as $blog)
                        <tr>
                            <td><span class="tbl-logo"><img src="{{ $blog->image_url }}" alt="{{ $blog->title }}"></span></td>
                            <td class="hm-table__name">{{ $blog->title }}</td>
                            <td>{{ $blog->category }}</td>
                            <td>{{ $blog->display_date }}</td>
                            <td>
                                <span class="flag-text">
                                    {{ collect([$blog->show_home ? "Home" : null, $blog->is_latest ? "Latest" : null])->filter()->implode(" · ") ?: "—" }}
                                </span>
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $blog->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $blog->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.blogs.edit', $blog) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.blogs.destroy', $blog) }}"
                                          data-confirm="Delete this blog post?" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 hm-table__sub">No blog posts yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $blogs])

@endsection
