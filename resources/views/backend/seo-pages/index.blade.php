@extends('backend.template.layouts.template-base')

@section('title', 'Page SEO')
@section('page_title', 'SEO')
@section('page_sub', 'Meta title, description and share image for each frontend page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Page SEO</h1>
            <p class="page-head__sub">{{ $pages->total() }} page{{ $pages->total() === 1 ? '' : 's' }} · overrides the meta written into each template</p>
        </div>
        <div class="d-inline-flex gap-2">
            <a href="{{ route('backend.settings.seo') }}" class="btn-ghost">Site Defaults</a>
            <a href="{{ route('backend.seo-pages.create') }}" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add Page
            </a>
        </div>
    </div>

    @include('backend.partials.flash')

    <div class="hm-card">
        @include('backend.partials.table-toolbar', ['placeholder' => 'Search page or route'])
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Page</th><th>Meta Title</th><th>Description</th><th>Robots</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td class="hm-table__name">
                                {{ $page->label }}
                                <span class="hm-table__sub">{{ $page->key }}</span>
                            </td>
                            <td>{{ Str::limit($page->title, 48) ?: '—' }}</td>
                            <td><span class="hm-table__sub">{{ Str::limit($page->meta_description, 70) ?: '—' }}</span></td>
                            <td><span class="hm-table__sub">{{ $page->meta_robots }}</span></td>
                            <td>
                                <span class="pill pill--tiny {{ $page->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $page->is_active ? 'Active' : 'Off' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('backend.seo-pages.edit', $page) }}" class="btn-ghost btn-icon" aria-label="Edit">
                                        <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    </a>
                                    <form method="POST" action="{{ route('backend.seo-pages.destroy', $page) }}"
                                          data-confirm="Delete this page SEO? The page falls back to the meta in its template." class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-danger-soft btn-icon" aria-label="Delete">
                                            <span class="act-ico act-ico--delete" aria-hidden="true"></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No page SEO yet. Add your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('backend.partials.table-pagination', ['paginator' => $pages])

@endsection
