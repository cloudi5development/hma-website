@extends('backend.template.layouts.template-base')

@section('title', 'Bulk Upload Courses')
@section('page_title', 'Bulk Upload Courses')
@section('page_sub', 'Courses')

@section('content')

    @php
        use App\Support\CourseImportTemplate as Template;

        $required = Template::requiredHeadings();
        $maxKb    = \App\Support\UploadLimit::cap(\App\Http\Requests\Backend\CourseBulkUploadRequest::PREFERRED_MAX_KB);
        $lists    = Template::dropdownLists();
    @endphp

    <div class="page-head">
        <div>
            <h1 class="page-head__title">Bulk Upload &amp; Export</h1>
            <p class="page-head__sub">
                Add many courses at once, or edit the ones you already have, from a single spreadsheet.
                Nothing is saved until you confirm.
            </p>
        </div>
        <a href="{{ route('backend.courses.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Courses
        </a>
    </div>

    @include('backend.partials.flash')

    {{-- ============================== STEP 1 ============================== --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <div class="bulk-step">
                <span class="bulk-step__num">1</span>
                <div>
                    <h2 class="bulk-step__title">Get your spreadsheet</h2>
                    <p class="bulk-step__lead">
                        Two ways to start, depending on whether you are changing courses that exist
                        or adding new ones.
                    </p>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-lg-6">
                    <div class="bulk-option">
                        <h3 class="bulk-option__title">Editing courses that already exist</h3>
                        <p class="bulk-option__text">
                            Download every course as a spreadsheet, change what you need, and upload the
                            same file back. Rows keep their <code>slug</code>, which is how the importer
                            knows to <strong>update</strong> those courses instead of creating copies.
                        </p>
                        <a href="{{ route('backend.courses.bulk.export') }}" class="btn-brand">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h10l6 6v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><path d="M14 4v6h6M9 14h6M9 17h4"/></svg>
                            Export Existing Courses
                        </a>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="bulk-option">
                        <h3 class="bulk-option__title">Adding brand-new courses</h3>
                        <p class="bulk-option__text">
                            Download the blank template — the same columns and the same dropdowns, with
                            no data in it. Leave <code>slug</code> empty and it is built from the course
                            name for you.
                        </p>
                        <a href="{{ route('backend.courses.bulk.template') }}" class="btn-ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                            Download Blank Template
                        </a>
                    </div>
                </div>
            </div>

            <p class="form-hint mt-3" style="margin-bottom:0">
                Both files carry the same three sheets — <strong>Courses</strong> (the grid you fill in,
                headings in row 1 and data from row 2), <strong>Instructions</strong>, and
                <strong>Allowed Values</strong>. Only the Courses sheet is read when you upload.
            </p>
        </div>
    </div>

    {{-- ============================== STEP 2 ============================== --}}
    <div class="hm-card mb-3">
        <div class="hm-card__body">
            <div class="bulk-step">
                <span class="bulk-step__num">2</span>
                <div>
                    <h2 class="bulk-step__title">Fill it in</h2>
                    <p class="bulk-step__lead">One row per course. The rules below are the same ones the
                        Add&nbsp;Course form applies.</p>
                </div>
            </div>

            {{-- The three things most likely to catch someone out, up front. --}}
            <div class="row g-3 mt-1">
                <div class="col-12 col-lg-4">
                    <div class="bulk-note bulk-note--key">
                        <h3 class="bulk-note__title">Create or update?</h3>
                        <p>
                            The <code>slug</code> column decides. A slug already in the catalogue
                            <strong>updates</strong> that course. A slug that is new — or left blank —
                            <strong>creates</strong> one.
                        </p>
                        <p style="margin-bottom:0">
                            Don't edit the slug of a row you mean to update, or you will get a second course.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="bulk-note bulk-note--warn">
                        <h3 class="bulk-note__title">Not in this file</h3>
                        <p>
                            <strong>Course image</strong> and <strong>brochure</strong> are uploads — add them
                            from Courses&nbsp;→&nbsp;Edit. An import never changes or clears them.
                        </p>
                        <p style="margin-bottom:0">
                            <strong>Batches</strong> live in Courses&nbsp;→&nbsp;Schedule and are untouched too.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="bulk-note">
                        <h3 class="bulk-note__title">Leaving things out</h3>
                        <p>
                            A column you delete from the sheet is <strong>left alone</strong> on an update —
                            it is not blanked.
                        </p>
                        <p style="margin-bottom:0">
                            So a file of just <code>course_name</code>, <code>slug</code> and
                            <code>duration</code> edits nothing but the duration.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Master data. Everything here is a dropdown in the sheet, so it is
                 picked rather than typed — but the values are worth showing. --}}
            <div class="form-section">
                <h2 class="form-section__title">Dropdown columns</h2>
            </div>
            <p class="form-hint">
                These are pick-lists in the spreadsheet, filled from what exists in this panel right now.
                Typing something that is not on the list is reported as an error rather than guessed at.
            </p>

            <div class="row g-3">
                @foreach ($lists as $column => $values)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="bulk-list">
                            <p class="bulk-list__name">{{ $column }}</p>
                            <p class="bulk-list__values">
                                @if ($column === 'category' && count($values) > 8)
                                    {{ implode(', ', array_slice($values, 0, 8)) }}
                                    <span class="hm-table__sub">and {{ count($values) - 8 }} more</span>
                                @elseif (count($values))
                                    {{ implode(', ', $values) }}
                                @else
                                    <span class="hm-table__sub">Nothing set up yet.</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (! count($lists['category'] ?? []))
                <p class="form-error">
                    There are no categories yet, so no course can be imported. Add one under
                    <a href="{{ route('backend.categories.index') }}">Courses → Categories</a> first.
                </p>
            @endif

            {{-- The full column reference, straight from the same definition the
                 template and the importer read. It cannot fall out of date. --}}
            <div class="form-section">
                <h2 class="form-section__title">
                    Every column <span class="form-hint" style="display:inline">({{ count(Template::COLUMNS) }} in total, {{ count($required) }} required)</span>
                </h2>
            </div>

            <div class="table-responsive">
                <table class="hm-table">
                    <thead>
                        <tr>
                            <th style="width:230px">Column</th>
                            <th style="width:110px">Required?</th>
                            <th>What to put in it</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (Template::COLUMNS as $column => $spec)
                            <tr>
                                <td class="hm-table__name"><code>{{ $column }}</code></td>
                                <td>
                                    @if ($spec['required'])
                                        <span class="pill pill--tiny pill--active">Required</span>
                                    @else
                                        <span class="pill pill--tiny">Optional</span>
                                    @endif
                                </td>
                                <td><span class="hm-table__sub">{{ $spec['help'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="form-hint mt-3" style="margin-bottom:0">
                <strong>Batches</strong> — start dates, end dates and the training Mode are not in this file;
                they belong to the batch and are managed at <strong>Courses → Schedule</strong>.
                <strong>Yes/No columns</strong> — Yes, No, Y, N, 1, 0, True, False, Active and Inactive are all understood.
                <strong>FAQs</strong> — up to {{ \App\Models\Course::MAX_FAQS }} per course; leave both cells of a pair blank
                to skip it, and note that on an update the FAQ columns replace that course's existing FAQs.
                <strong>Learning outcomes</strong> — one per line, using Alt+Enter inside the cell.
            </p>
        </div>
    </div>

    {{-- ============================== STEP 3 ============================== --}}
    <form method="POST" action="{{ route('backend.courses.bulk.validate') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="hm-card mb-3">
            <div class="hm-card__body">
                <div class="bulk-step">
                    <span class="bulk-step__num">3</span>
                    <div>
                        <h2 class="bulk-step__title">Upload and check it</h2>
                        <p class="bulk-step__lead">
                            The file is read and every row checked <strong>before</strong> anything is written.
                        </p>
                    </div>
                </div>

                {{-- A real <label> wrapping a real file input, so it works by click
                     and by keyboard with no JS; drag and drop is added on top. --}}
                <label class="hm-drop" id="bulkDrop">
                    <input type="file" id="bulkFile" name="file" accept=".xlsx,.csv"
                           class="@error('file') is-invalid @enderror">
                    <span class="hm-drop__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 16V4m0 0L8 8m4-4 4 4"/><path d="M4 17v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2"/>
                        </svg>
                    </span>
                    <span class="hm-drop__title" id="bulkDropTitle">Drop your file here, or click to choose</span>
                    <span class="hm-drop__hint">.xlsx or .csv · up to {{ \App\Support\UploadLimit::label($maxKb) }}</span>
                </label>

                @error('file') <p class="form-error">{{ $message }}</p> @enderror

                <p class="form-hint mt-3" style="margin-bottom:0">
                    Next you will see a preview — how many rows will be created, how many will update an
                    existing course, and anything that cannot be imported with the reason why. You can
                    download an error report from there, and nothing is saved until you press
                    <strong>Import</strong>.
                </p>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                Check the File
            </button>
            <a href="{{ route('backend.courses.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

@endsection

@push('styles')
    <style>
        /* All built from the panel's own tokens, so this page reads as part of
           the existing form language rather than a bolted-on screen. */
        .bulk-step { display: flex; align-items: flex-start; gap: 14px; }
        .bulk-step__num {
            flex: 0 0 auto;
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            background: var(--brand, #9C4A22);
            color: #fff;
            font-weight: 700; font-size: 15px;
        }
        .bulk-step__title { margin: 4px 0 2px; font-size: 17px; font-weight: 700; color: var(--ink, #2E2620); }
        .bulk-step__lead  { margin: 0; font-size: 13.5px; color: var(--muted, #8A7E70); }

        .bulk-option {
            height: 100%;
            padding: 18px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 12px;
            background: var(--surface-2, #FBF7F1);
        }
        .bulk-option__title { margin: 0 0 6px; font-size: 14.5px; font-weight: 700; color: var(--ink, #2E2620); }
        .bulk-option__text  { margin: 0 0 14px; font-size: 13px; line-height: 1.7; color: var(--muted, #8A7E70); }

        .bulk-note {
            height: 100%;
            padding: 16px;
            border: 1px solid var(--line, #E7DED2);
            border-left: 3px solid var(--line, #E7DED2);
            border-radius: 10px;
        }
        .bulk-note--key  { border-left-color: #1F7A4D; }
        .bulk-note--warn { border-left-color: #A6741F; }
        .bulk-note__title { margin: 0 0 6px; font-size: 13.5px; font-weight: 700; color: var(--ink, #2E2620); }
        .bulk-note p { margin: 0 0 8px; font-size: 12.5px; line-height: 1.7; color: var(--muted, #8A7E70); }

        .bulk-list {
            height: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 10px;
        }
        .bulk-list__name   { margin: 0 0 4px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12.5px; font-weight: 700; color: var(--brand, #9C4A22); }
        .bulk-list__values { margin: 0; font-size: 12.5px; line-height: 1.6; color: var(--muted, #8A7E70); }

        .hm-card__body code {
            padding: 1px 5px;
            border-radius: 4px;
            background: var(--surface-2, #FBF7F1);
            font-size: 12px;
            color: var(--brand, #9C4A22);
        }

        /* Drop zone */
        .hm-drop {
            position: relative;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 6px;
            padding: 34px 20px;
            text-align: center;
            border: 2px dashed var(--line, #E7DED2);
            border-radius: 14px;
            background: var(--surface-2, #FBF7F1);
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease;
        }
        .hm-drop:hover,
        .hm-drop:focus-within { border-color: var(--brand, #9C4A22); background: #FFF9F2; }
        .hm-drop.is-dragging  { border-color: var(--brand, #9C4A22); background: #FDF1E4; }
        .hm-drop input[type="file"] { position: absolute; width: 1px; height: 1px; opacity: 0; overflow: hidden; }
        .hm-drop__icon  { color: var(--brand, #9C4A22); }
        .hm-drop__icon svg { width: 34px; height: 34px; }
        .hm-drop__title { font-weight: 600; color: var(--ink, #2E2620); }
        .hm-drop__hint  { font-size: 13px; color: var(--muted, #8A7E70); }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            'use strict';
            var drop  = document.getElementById('bulkDrop');
            var input = document.getElementById('bulkFile');
            var title = document.getElementById('bulkDropTitle');
            if (!drop || !input) return;

            function named() {
                if (input.files && input.files.length) title.textContent = input.files[0].name;
            }

            input.addEventListener('change', named);

            ['dragenter', 'dragover'].forEach(function (type) {
                drop.addEventListener(type, function (e) {
                    e.preventDefault();
                    drop.classList.add('is-dragging');
                });
            });

            ['dragleave', 'drop'].forEach(function (type) {
                drop.addEventListener(type, function (e) {
                    e.preventDefault();
                    drop.classList.remove('is-dragging');
                });
            });

            drop.addEventListener('drop', function (e) {
                if (!e.dataTransfer || !e.dataTransfer.files.length) return;
                // Hand the dropped file to the real input so the form posts it
                // exactly as a picked one — no separate upload path.
                input.files = e.dataTransfer.files;
                named();
            });
        })();
    </script>
@endpush
