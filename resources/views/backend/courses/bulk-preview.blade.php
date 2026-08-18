@extends('backend.template.layouts.template-base')

@section('title', 'Bulk Course Import — Preview')
@section('page_title', 'Bulk Course Import')
@section('page_sub', 'Preview')

@section('content')

    @php
        use App\Services\CourseBulkImportService as Import;
        $blocked = $summary['ready'] === 0;
    @endphp

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Preview</h1>
            <p class="page-head__sub">{{ $report['file'] }} — nothing has been saved yet.</p>
        </div>
        <form method="POST" action="{{ route('backend.courses.bulk.cancel') }}">
            @csrf
            <button type="submit" class="btn-ghost">Cancel &amp; start over</button>
        </form>
    </div>

    @include('backend.partials.flash')

    {{-- ----------------------------- TOTALS ----------------------------- --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['label' => 'Total rows',        'value' => $summary['total'],      'tone' => 'plain'],
            ['label' => 'New courses',       'value' => $summary['create'],     'tone' => 'ok'],
            ['label' => 'Existing, updated', 'value' => $summary['update'],     'tone' => 'info'],
            ['label' => 'Errors',            'value' => $summary['errors'],     'tone' => 'bad'],
            ['label' => 'Duplicates',        'value' => $summary['duplicates'], 'tone' => 'warn'],
        ] as $stat)
            <div class="col-6 col-lg">
                <div class="hm-card h-100">
                    <div class="hm-card__body">
                        <p class="form-label" style="margin-bottom:4px">{{ $stat['label'] }}</p>
                        <p class="bulk-stat bulk-stat--{{ $stat['tone'] }}">{{ $stat['value'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- How a row was matched, said once rather than repeated per row. --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <p class="form-hint" style="margin:0">
                Rows are matched on <strong>slug</strong>. A slug already in the catalogue
                <strong>updates</strong> that course; anything else creates a new one.
                An update never changes a course's image, brochure or batches.
            </p>
        </div>
    </div>

    @if (($report['truncated'] ?? 0) > 0)
        {{-- Never let a cap pass silently — a file that was cut short must say so. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <p class="form-hint" style="color:#A6741F;margin:0">
                    <strong>This file is larger than one import allows.</strong>
                    The first {{ number_format(\App\Imports\CourseRowsImport::MAX_ROWS) }} course rows were read;
                    {{ number_format($report['truncated']) }} further row{{ $report['truncated'] === 1 ? ' was' : 's were' }}
                    not. Import these, then upload the rest in a second file.
                </p>
            </div>
        </div>
    @endif

    @if ($blocked)
        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <p class="form-error" style="margin:0">
                    No row in this file can be imported. Fix the problems listed below — or download the
                    error report — and upload the file again.
                </p>
            </div>
        </div>
    @endif

    {{-- ------------------------------ ROWS ------------------------------ --}}
    <div class="hm-card">
        <div class="table-responsive">
            <table class="hm-table">
                <thead>
                    <tr>
                        <th style="width:80px">Row</th>
                        <th>Course Name</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Action</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($previewRows as $row)
                        <tr>
                            <td class="hm-table__sub">{{ $row['line'] }}</td>
                            <td class="hm-table__name">{{ $row['course_name'] ?: '—' }}</td>
                            <td>{{ $row['category'] ?: '—' }}</td>
                            <td class="hm-table__sub">{{ $row['slug'] ?: '—' }}</td>
                            <td>
                                @if ($row['status'] === Import::CREATE)
                                    <span class="pill pill--tiny pill--active">Create</span>
                                @elseif ($row['status'] === Import::UPDATE)
                                    <span class="pill pill--tiny pill--info">Update</span>
                                @elseif ($row['status'] === Import::DUPLICATE)
                                    <span class="pill pill--tiny pill--inactive">Duplicate</span>
                                @else
                                    <span class="pill pill--tiny pill--inactive">Error</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['errors'])
                                    <span class="hm-table__sub">{{ implode(' ', $row['errors']) }}</span>
                                @else
                                    <span class="hm-table__sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 hm-table__sub">No rows to show.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($hiddenRows > 0)
            <div class="hm-card__body">
                <p class="form-hint" style="margin:0">
                    Showing the first {{ count($previewRows) }} rows. The remaining
                    {{ number_format($hiddenRows) }} are included in the import and in the error report.
                </p>
            </div>
        @endif
    </div>

    {{-- ---------------------------- ACTIONS ---------------------------- --}}
    <div class="d-flex gap-2 flex-wrap mt-3">
        <form method="POST" action="{{ route('backend.courses.bulk.import') }}"
              @if (! $blocked && ($summary['errors'] || $summary['duplicates']))
                  data-confirm="{{ $summary['create'] }} course(s) will be created and {{ $summary['update'] }} updated. The {{ $summary['errors'] + $summary['duplicates'] }} row(s) with problems will be skipped — download the error report first if you need it. Continue?"
              @endif>
            @csrf
            <button type="submit" class="btn-brand" @disabled($blocked)>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Import {{ $summary['ready'] }} Row{{ $summary['ready'] === 1 ? '' : 's' }}
                ({{ $summary['create'] }} new, {{ $summary['update'] }} updated)
            </button>
        </form>

        @if ($summary['errors'] || $summary['duplicates'])
            <a href="{{ route('backend.courses.bulk.errors') }}" class="btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                Download Error Report
            </a>
        @endif

        <form method="POST" action="{{ route('backend.courses.bulk.cancel') }}">
            @csrf
            <button type="submit" class="btn-ghost">Cancel</button>
        </form>
    </div>

@endsection

@push('styles')
    <style>
        .bulk-stat { margin: 0; font-size: 30px; font-weight: 700; line-height: 1.1; }
        .bulk-stat--plain { color: var(--ink, #2E2620); }
        .bulk-stat--ok    { color: #1F7A4D; }
        .bulk-stat--bad   { color: #B3261E; }
        .bulk-stat--warn  { color: #A6741F; }
        .bulk-stat--info  { color: #1F5E9E; }
        .pill--info { background: #E7F0FA; color: #1F5E9E; }
    </style>
@endpush
