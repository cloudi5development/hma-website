@extends('backend.template.layouts.template-base')

@section('title', 'About Us')
@section('page_title', 'About Us')
@section('page_sub', 'The four blocks on the About Us page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">About Us</h1>
            <p class="page-head__sub">Our Story · Our Purpose · Our Features · Our Approach</p>
        </div>
        <a href="{{ route('frontend.about-us') }}" target="_blank" rel="noopener" class="btn-ghost">
            <span class="act-ico act-ico--view" aria-hidden="true"></span>
            View on site
        </a>
    </div>

    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th>Section</th><th>Heading</th><th>Rows</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr>
                            <td class="hm-table__name">
                                {{ $section->name }}
                                <span class="hm-table__sub">{{ $section->label }}</span>
                            </td>
                            <td>
                                @if ($section->title)
                                    {{ Str::limit($section->title, 46) }}
                                @else
                                    <span class="hm-table__sub">Label only</span>
                                @endif
                            </td>
                            <td>{{ $section->items_count }} {{ Str::lower($section->itemLabel($section->items_count !== 1)) }}</td>
                            <td>
                                <span class="pill pill--tiny {{ $section->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $section->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('backend.about-sections.edit', $section->key) }}" class="btn-ghost">
                                    <span class="act-ico act-ico--edit" aria-hidden="true"></span>
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
