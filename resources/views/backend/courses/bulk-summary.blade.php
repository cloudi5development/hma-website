@extends('backend.template.layouts.template-base')

@section('title', 'Bulk Course Import — Completed')
@section('page_title', 'Bulk Course Import')
@section('page_sub', 'Completed')

@section('content')

    @php $skipped = $summary['duplicates']; @endphp

    @php
        $tiles = [
            ['label' => 'Successfully created', 'value' => $summary['create'],  'tone' => 'ok'],
            ['label' => 'Successfully updated', 'value' => $summary['update'],  'tone' => 'info'],
            ['label' => 'Skipped (duplicates)', 'value' => $skipped,            'tone' => 'warn'],
            ['label' => 'Failed',               'value' => $summary['errors'],  'tone' => 'bad'],
            ['label' => 'Total processed',      'value' => $summary['total'],   'tone' => 'plain'],
        ];
    @endphp

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Import completed</h1>
            <p class="page-head__sub">{{ $report['file'] }}</p>
        </div>
    </div>

    @include('backend.partials.flash')

    <div class="row g-3 mb-3">
        @foreach ($tiles as $stat)
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

    <div class="hm-card mb-3">
        <div class="hm-card__body">
            @if ($summary['create'])
                <p class="form-hint" style="margin:0 0 8px">
                    The <strong>{{ $summary['create'] }} newly created</strong>
                    course{{ $summary['create'] === 1 ? ' has' : 's have' }}
                    <strong>no image or brochure yet</strong> — open each one under Courses → Edit to
                    upload them, exactly as you do for a course added by hand.
                </p>
            @endif

            @if ($summary['update'])
                <p class="form-hint" style="margin:0 0 8px">
                    The {{ $summary['update'] }} updated course{{ $summary['update'] === 1 ? '' : 's' }}
                    kept {{ $summary['update'] === 1 ? 'its' : 'their' }} existing image, brochure and batches —
                    an import never touches those.
                </p>
            @endif

            @if ($summary['ready'])
                <p class="form-hint" style="margin:0">
                    Batches are separate: add them under <strong>Courses → Schedule</strong>.
                </p>
            @else
                <p class="form-hint" style="margin:0">Nothing was imported from this file.</p>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('backend.courses.index') }}" class="btn-brand">View Courses</a>
        <a href="{{ route('backend.courses.bulk.form') }}" class="btn-ghost">Upload Another File</a>

        @if ($summary['errors'] || $skipped)
            <a href="{{ route('backend.courses.bulk.errors') }}" class="btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                Download Error Report
            </a>
        @endif
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
    </style>
@endpush
