@extends('backend.template.layouts.template-base')

@section('title', 'Placement Readiness')
@section('page_title', 'Placement Readiness')
@section('page_sub', 'Every block of the Placement Readiness page')

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Placement Readiness</h1>
            <p class="page-head__sub">{{ $sections->count() }} sections · the page at /placement-readiness</p>
        </div>
        <a href="{{ route('frontend.placement-readiness') }}" target="_blank" rel="noopener" class="btn-ghost">
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
                                <span class="hm-table__sub">{{ $section->eyebrow ?: 'No label' }}</span>
                            </td>
                            <td>
                                @if ($section->title)
                                    {{ Str::limit($section->title, 52) }}
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($section->groups())
                                    <span class="flag-text">
                                        {{ collect($section->groups())
                                            ->map(fn ($settings, $group) => $section->itemsIn($group)->count() . ' ' . Str::lower($settings['plural']))
                                            ->implode(' · ') }}
                                    </span>
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill pill--tiny {{ $section->is_active ? 'pill--active' : 'pill--inactive' }}">
                                    {{ $section->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('backend.placement-readiness.edit', $section->key) }}" class="btn-ghost">
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
