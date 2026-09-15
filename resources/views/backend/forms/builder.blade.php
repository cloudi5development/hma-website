@extends('backend.template.layouts.template-base')

@php
    $editing = $form->exists;

    /*
     * The form as pages → sections → question rows.
     *
     * Always that nesting, whatever structure the form is: a plain form is one
     * page holding one section, with the chrome of both hidden. It is what lets
     * the four structures be one builder rather than three — see
     * App\Support\FormBuilderTree.
     *
     * old() is handled in there too, so a failed save gives the admin back the
     * pages and sections they had added as well as the questions.
     */
    $tree = \App\Support\FormBuilderTree::for($form);

    $structure = old('structure_type', $editing ? $form->structure() : \App\Models\Form::PLAIN);

    // A small line drawing for each structure, so the cards can be told apart
    // at a glance rather than read. Kept here rather than on the model: it is
    // the only place they are drawn.
    $structureIcons = [
        \App\Models\Form::PLAIN          => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        \App\Models\Form::SECTIONS       => '<rect x="4" y="3" width="16" height="7" rx="1.5"/><rect x="4" y="14" width="16" height="7" rx="1.5"/><path d="M7 6.5h6M7 17.5h6"/>',
        \App\Models\Form::PAGES          => '<rect x="3" y="6" width="11" height="13" rx="1.5"/><path d="M17 8h4M17 12h4M17 16h2"/>',
        \App\Models\Form::PAGES_SECTIONS => '<rect x="3" y="5" width="10" height="6" rx="1.5"/><rect x="3" y="14" width="10" height="5" rx="1.5"/><path d="M17 7h4M17 12h4M17 17h3"/>',
    ];
@endphp

@section('title', $editing ? 'Edit ' . $form->name : 'Create Form')
@section('page_title', $editing ? 'Edit Form' : 'Create Form')
@section('page_sub', 'Forms')

@section('content')

    {{-- Just the way back. Preview, Share and Responses are all reachable from
         the forms list and from the form's own page; repeating them here made a
         screen for editing questions look like a dashboard. --}}
    <div class="page-head">
        <a href="{{ route('backend.forms.index') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Back to Forms
        </a>
    </div>

    <form method="POST" action="{{ $editing ? route('backend.forms.update', $form) : route('backend.forms.store') }}"
          id="formBuilder" novalidate>
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- The status the save lands on. A form is published the moment it is
             created — the admin has just been shown its link and told to share
             it, so a draft would be a link that does not work. --}}
        {{-- Creating publishes; editing keeps whatever the form already is.
             Written out rather than `$form->status ?: PUBLISHED`, which never
             fell through: a new Form is built carrying 'draft', and 'draft' is
             truthy — so every form made in the panel came out closed, with a
             link that showed the closed message. --}}
        <input type="hidden" name="status" id="formStatus"
               value="{{ old('status', $editing ? $form->status : \App\Models\Form::PUBLISHED) }}">

        {{-- Set only by the "unsaved changes" dialog: where to go once the save
             has worked, so the link that was clicked still takes the admin
             there. FormController::afterSave accepts only this site's addresses. --}}
        <input type="hidden" name="after_save" value="" data-after-save>

        {{-- ============================= DETAILS =============================
             Two boxes. The form's name is its heading and its link as well, so
             there is nothing else here to fill in. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__body">

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="name">Form Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control-hm @error('name') is-invalid @enderror"
                                   value="{{ old('name', $form->name) }}"
                                   placeholder="e.g. Course Enquiry" autocomplete="off">
                            {{-- The path, not the whole absolute URL. In a
                                 half-width column "http://127.0.0.1:8000/forms/…"
                                 ran past the edge and was clipped mid-word. The
                                 full address is on the Generate Link dialog and
                                 behind Copy Link in the forms list, which is
                                 where anyone actually wants to take it from. --}}
                            <p class="form-hint">
                                Used as the heading visitors read, and as the form's web link:
                                <span class="fb-linkhint" data-link-preview>/forms/{{ $form->slug ?: '…' }}</span>
                            </p>
                            @error('name') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-row" style="margin-bottom:0">
                            <label class="form-label" for="description">Form Description <span class="form-hint" style="display:inline">(optional)</span></label>
                            <textarea id="description" name="description" rows="2"
                                      class="form-control-hm @error('description') is-invalid @enderror"
                                      placeholder="Shown under the heading, e.g. Please complete the form below and our team will contact you.">{{ old('description', $form->description) }}</textarea>
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ============================ STRUCTURE ============================
             What shape this form is. Chosen first because it decides what the
             rest of the screen offers — but never what it can hold: the four
             structures share one set of questions, and moving between them
             keeps every one of them. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__head">
                <h2 class="hm-card__title">Form Structure</h2>
            </div>
            <div class="hm-card__body">
                <div class="fb-structs" role="radiogroup" aria-label="Form structure">
                    @foreach (\App\Models\Form::STRUCTURES as $value => $spec)
                        <label class="fb-struct">
                            <input type="radio" name="structure_type" value="{{ $value }}"
                                   @checked($structure === $value) data-structure>
                            <span class="fb-struct__box">
                                <svg class="fb-struct__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true">{!! $structureIcons[$value] !!}</svg>
                                <span class="fb-struct__name">{{ $spec['label'] }}</span>
                                <span class="fb-struct__hint">{{ $spec['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- Said plainly, because the alternative is an admin who is
                     afraid to try the other three. --}}
                <p class="form-hint mt-2">
                    You can change this at any time. Your questions are kept whichever you pick —
                    switching to a simpler structure only drops the page and section headings.
                </p>
            </div>
        </div>

        {{-- ============================= FORM TYPE ===========================
             Standard or quiz. A quiz only adds a Correct answer picker to its
             Multiple choice questions — nothing is scored — so a standard form
             is exactly what every form was before this existed. --}}
        <div class="hm-card mb-3">
            <div class="hm-card__head">
                <h2 class="hm-card__title">Form Type</h2>
            </div>
            <div class="hm-card__body">
                <div class="fb-structs fb-structs--two" role="radiogroup" aria-label="Form type">
                    @foreach (\App\Models\Form::FORM_TYPES as $value => $spec)
                        <label class="fb-struct">
                            <input type="radio" name="form_type" value="{{ $value }}"
                                   @checked(old('form_type', $editing && $form->isQuiz() ? \App\Models\Form::QUIZ : \App\Models\Form::STANDARD) === $value)
                                   data-form-type>
                            <span class="fb-struct__box">
                                <svg class="fb-struct__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    @if ($value === \App\Models\Form::QUIZ)
                                        <circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>
                                    @else
                                        <rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/>
                                    @endif
                                </svg>
                                <span class="fb-struct__name">{{ $spec['label'] }}</span>
                                <span class="fb-struct__hint">{{ $spec['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============================== QUESTIONS ==========================
             One shell for all four structures. The page → section → questions
             nesting is always here; the structure only decides which headings
             are shown. See App\Support\FormBuilderTree. --}}
        @error('fields') <p class="form-error mb-2">{{ $message }}</p> @enderror

        <div class="fb-shell" data-shell>

            {{-- The steps, and the button that adds one — on the same line,
                 above the page it is showing. Adding a page belongs beside the
                 pages rather than under the questions, which is a long way from
                 anything it has to do with. --}}
            <div class="fb-bar" data-needs="pages" hidden>
                {{-- Built from the pages below by the builder script so the two
                     cannot drift, and draggable to reorder. --}}
                <nav class="fb-tabs" data-page-tabs aria-label="Pages"></nav>

                <button type="button" class="fb-addbtn" data-page-add>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add Page
                </button>
            </div>

            <div data-pages>
                @foreach ($tree as $page)
                    @include('backend.forms.partials.page-card', ['page' => $page])
                @endforeach
            </div>
        </div>


        {{-- ============================== ACTIONS ============================
             Creating: Generate Link shows the form's web address in a dialog,
             and Create Form inside that dialog is what actually saves it. The
             link is fetched from the server rather than guessed here, so what
             the admin is shown is the address the form will really have.

             Editing: the link already exists and has been shared, so this is
             just a save. --}}
        {{-- Shown after a bulk upload. The imported questions are on screen but
             not saved until the form is — the same as questions typed in — and
             an admin who has just watched fifty questions appear could easily
             believe otherwise and close the tab. --}}
        <p class="fb-unsaved" data-unsaved hidden role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/></svg>
            <span data-unsaved-text></span>
        </p>

        <div class="fb-actions">
            @if ($editing)
                <button type="submit" class="btn-brand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Save Changes
                </button>
                <a href="{{ route('backend.forms.preview', $form) }}" class="btn-ghost">Preview</a>
            @else
                <button type="button" class="btn-brand" id="generateLink">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                    Generate Link
                </button>
            @endif
            <a href="{{ route('backend.forms.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    @unless ($editing)
        {{-- The dialog the Generate Link button opens. It reuses the panel the
             admin's confirm dialog uses, so it looks like the rest of the panel
             and needs no styling of its own. The Create Form button submits the
             builder above — this is a confirmation step, not a second form. --}}
        <div class="hm-dialog" id="linkDialog" role="dialog" aria-modal="true" aria-labelledby="linkDialogTitle">
            <div class="hm-dialog__panel">
                <span class="hm-dialog__icon hm-dialog__icon--brand" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
                </span>
                <h2 class="hm-dialog__title" id="linkDialogTitle">Your form's link</h2>
                <p class="hm-dialog__text">
                    This is where people will open the form. It starts working as soon as you create it.
                </p>

                <div class="fb-linkbox">
                    <input type="text" id="generatedLink" readonly value="" aria-label="Form link">
                    <button type="button" class="btn-ghost" data-copy="#generatedLink">Copy</button>
                </div>

                <div class="hm-dialog__actions">
                    <button type="button" class="btn-ghost" id="linkDialogCancel">Back</button>
                    <button type="button" class="btn-brand" id="createFormBtn">Create Form</button>
                </div>
            </div>
        </div>
    @endunless

    {{-- =========================== UNSAVED CHANGES ==========================
         Asked when the admin clicks their way off this screen — a menu link,
         Back, Cancel, sign out — with changes that are not saved. The admin
         dialog's own look; three answers rather than two, because "save first"
         is the answer most people want and the confirm dialog cannot offer it.

         Closing the tab, reloading or the browser's Back button cannot show
         this: browsers allow only their own built-in warning there, and that is
         what those get. --}}
    <div class="hm-dialog" id="leaveDialog" role="alertdialog" aria-modal="true"
         aria-labelledby="leaveTitle" aria-describedby="leaveText">
        <div class="hm-dialog__panel fb-leave">
            <span class="hm-dialog__icon hm-dialog__icon--brand" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            </span>
            <h2 class="hm-dialog__title" id="leaveTitle">
                {{ $editing ? 'Save your changes?' : 'Create this form before leaving?' }}
            </h2>
            <p class="hm-dialog__text" id="leaveText">
                {{ $editing
                    ? 'You have changes to this form that are not saved yet. If you leave now, they will be lost.'
                    : 'This form has not been created yet. If you leave now, everything you have added will be lost.' }}
            </p>

            <div class="fb-leave__actions">
                <button type="button" class="btn-brand" data-leave-save>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    {{ $editing ? 'Save Changes' : 'Create Form' }}
                </button>
                <button type="button" class="btn-ghost fb-leave__discard" data-leave-discard>Leave without saving</button>
                <button type="button" class="btn-ghost" data-leave-stay>Stay on this page</button>
            </div>
        </div>
    </div>

    {{-- ============================ BULK UPLOAD ============================
         Outside the builder's <form>, so the file input can never be posted
         with a save. The panel reuses the admin dialog's backdrop and motion;
         .fb-bulk only widens it, because a preview table does not fit in the
         confirm dialog's 400px.

         Two stages in one panel: choosing a file, and reviewing what it holds.
         Nothing is written by either — see FormImportService. --}}
    <div class="hm-dialog" id="bulkDialog" role="dialog" aria-modal="true" aria-labelledby="bulkTitle">
        <div class="hm-dialog__panel fb-bulk">

            <header class="fb-bulk__head">
                <span class="hm-dialog__icon hm-dialog__icon--brand fb-bulk__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0-4 4m4-4 4 4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                </span>
                <div class="fb-bulk__heading">
                    <h2 class="hm-dialog__title" id="bulkTitle">Bulk Upload Fields</h2>
                    <p class="fb-bulk__sub">Upload an Excel file to create multiple form fields at once.</p>
                </div>
                <button type="button" class="fb-ico" data-bulk-close aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </header>

            {{-- ---------------------------- CHOOSE ---------------------------- --}}
            <div class="fb-bulk__body" data-bulk-stage="choose">
                <p class="fb-bulk__target" data-bulk-target></p>

                <section class="fb-bulk__step">
                    <span class="fb-bulk__num" aria-hidden="true">1</span>
                    <div class="fb-bulk__stepbody">
                        <h3 class="fb-bulk__steptitle">Download Template</h3>
                        {{-- A quiz has its own template — Question and Correct
                             Answer, no Placeholder — so the link and this line
                             are set for the form type picked above each time the
                             dialog opens. --}}
                        <p class="form-hint" data-bulk-template-hint>Download the template, fill in your questions and field details, then upload it here.</p>
                        <a href="{{ route('backend.forms.bulk-template') }}" class="btn-soft" data-bulk-template download
                           data-standard="{{ route('backend.forms.bulk-template') }}"
                           data-quiz="{{ route('backend.forms.bulk-template', ['type' => \App\Models\Form::QUIZ]) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12m0 0-4-4m4 4 4-4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                            Download Excel Template
                        </a>
                    </div>
                </section>

                <section class="fb-bulk__step">
                    <span class="fb-bulk__num" aria-hidden="true">2</span>
                    <div class="fb-bulk__stepbody">
                        <h3 class="fb-bulk__steptitle">Upload Excel File</h3>

                        {{-- A label wrapping the input, so the whole zone opens the
                             picker with no script, and a keyboard reaches it. --}}
                        <label class="fb-drop" data-bulk-drop>
                            <input type="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                   data-bulk-file class="fb-drop__input">
                            <svg class="fb-drop__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13l2 2 4-4"/></svg>
                            <span class="fb-drop__title">Drag and drop your Excel file here</span>
                            <span class="fb-drop__or">or</span>
                            <span class="btn-brand fb-drop__btn">Choose Excel File</span>
                            <span class="fb-drop__hint">Supported formats: .xlsx, .xls · up to {{ \App\Support\FormImportSheet::MAX_KB / 1024 }} MB · {{ \App\Support\FormImportSheet::MAX_ROWS }} questions</span>
                        </label>

                        <div class="fb-file" data-bulk-chip hidden>
                            <svg class="fb-file__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg>
                            <div class="fb-file__meta">
                                <span class="fb-file__name" data-bulk-name></span>
                                <span class="fb-file__size" data-bulk-size></span>
                            </div>
                            <span class="fb-file__status" data-bulk-status></span>
                            <button type="button" class="btn-ghost fb-file__change" data-bulk-change>Change file</button>
                        </div>

                        <p class="fb-bulk__error" data-bulk-error role="alert" hidden></p>
                    </div>
                </section>
            </div>

            {{-- ---------------------------- REVIEW ---------------------------- --}}
            <div class="fb-bulk__body" data-bulk-stage="review" hidden>
                <div class="fb-bulk__summary" data-bulk-summary></div>
                <ul class="fb-bulk__problems" data-bulk-problems hidden></ul>

                <div class="fb-bulk__tablewrap">
                    <table class="fb-bulk__table">
                        <thead>
                            <tr>
                                <th>Row</th><th>Order</th><th data-bulk-labelcol>Label</th><th>Type</th><th>Required</th>
                                <th>Options</th><th data-bulk-correctcol>Correct Answer</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody data-bulk-rows></tbody>
                    </table>
                </div>
            </div>

            <footer class="fb-bulk__foot">
                <button type="button" class="btn-ghost" data-bulk-close>Cancel</button>
                <button type="button" class="btn-ghost" data-bulk-back hidden>Choose another file</button>
                <button type="button" class="btn-brand" data-bulk-import disabled hidden>Import Fields</button>
            </footer>
        </div>
    </div>

    {{-- Templates cloned by the builder script. __I__ becomes a field row key,
         __O__ an option key, __P__ a page key and __S__ a section key. Kept out
         of the form so their inputs never post. --}}
    <template id="fieldTemplate">
        @include('backend.forms.partials.field-row', ['r' => '__I__', 'row' => [], 'pageRef' => '__P__', 'sectionRef' => '__S__'])
    </template>
    <template id="optionTemplate">
        @include('backend.forms.partials.option-row', ['r' => '__I__', 'o' => '__O__', 'option' => []])
    </template>
    <template id="sectionTemplate">
        @include('backend.forms.partials.section-card', [
            'section' => ['ref' => '__S__', 'id' => null, 'title' => '', 'description' => '', 'fields' => []],
            'pageRef' => '__P__',
        ])
    </template>
    <template id="pageTemplate">
        @include('backend.forms.partials.page-card', [
            'page' => [
                'ref' => '__P__', 'id' => null, 'title' => '', 'description' => '',
                'sections' => [['ref' => '__S__', 'id' => null, 'title' => '', 'description' => '', 'fields' => []]],
            ],
        ])
    </template>

@endsection

@push('scripts')
    <script>
        /* =====================================================================
           Form builder
           ---------------------------------------------------------------------
           Everything the admin does to the form's shape happens here: pages,
           sections, questions — adding, deleting, reordering, collapsing, and
           switching a question's type.

           Four things are worth knowing before changing any of it:

           1. THE NESTING IS ALWAYS THERE. Every form on this screen is
              pages → sections → questions, whatever structure it is. A plain
              form is one page holding one section with the chrome of both
              hidden. Choosing a structure only decides which headings the admin
              can see; it never moves a question. That is what makes switching
              between the four safe — see App\Support\FormBuilderTree.

           2. A row's position in the DOM IS its display order. A form posts its
              inputs in document order, so moving an element is the whole of
              reordering — there is no order field to keep in step.

           3. WHERE a question sits is posted explicitly, not inferred. Each row
              carries page_ref / section_ref, stamped from the DOM just before
              the form submits. Dragging a question into another section would
              otherwise mean rewriting every input name on it.

           4. Which settings a field shows is read from the type registry
              (App\Support\FormFieldType), passed in below. Adding a field type
              in PHP makes it appear here with the right panels and no change to
              this script.
           ===================================================================== */
        (function () {
            'use strict';

            var TYPES = @json(\App\Support\FormFieldType::forJavascript());

            var form       = document.getElementById('formBuilder'),
                shell      = document.querySelector('[data-shell]'),
                pagesBox   = shell && shell.querySelector('[data-pages]'),
                tabsBox    = shell && shell.querySelector('[data-page-tabs]'),
                fieldTpl   = document.getElementById('fieldTemplate'),
                optionTpl  = document.getElementById('optionTemplate'),
                sectionTpl = document.getElementById('sectionTemplate'),
                pageTpl    = document.getElementById('pageTemplate'),
                MAX          = {{ \App\Services\FormBuilderService::MAX_FIELDS }},
                MAX_PAGES    = {{ \App\Services\FormBuilderService::MAX_PAGES }},
                MAX_SECTIONS = {{ \App\Services\FormBuilderService::MAX_SECTIONS }},
                active       = 0;

            if (!form || !shell || !pagesBox) return;

            /* ---- Keys for rows this browser has added -------------------------
               Salted per page load. A failed save hands back the refs the last
               attempt posted ("np1", "ns2"); a counter starting from zero again
               would mint those same keys for new rows and two pages would
               collide in the payload. */
            var salt = Math.random().toString(36).slice(2, 6),
                seq  = 0;

            // True while a bulk upload is being laid into the builder. The add
            // functions skip focusing and renumbering then, and it is settled
            // once at the end.
            var importing = false;

            function ref(kind) { return 'n' + kind + salt + (++seq); }

            /* ---- What shape the admin has chosen ------------------------------ */
            function structure() {
                var picked = form.querySelector('[data-structure]:checked');
                return picked ? picked.value : 'plain';
            }

            function hasPages(s)    { return s === 'pages' || s === 'pages_sections'; }
            function hasSections(s) { return s === 'sections' || s === 'pages_sections'; }

            function pageCards()    { return Array.prototype.slice.call(pagesBox.querySelectorAll('[data-page]')); }
            function sectionsOf(p)  { return Array.prototype.slice.call(p.querySelectorAll('[data-section]')); }
            function allRows()      { return Array.prototype.slice.call(shell.querySelectorAll('[data-row]')); }

            /* ---- Showing the structure the admin picked ------------------------
               Nothing is moved and nothing is thrown away: this only decides
               which headings are on screen. A form switched from multi-page to
               plain still holds every question, all of its pages still shown
               one under another with their headings hidden, and the save is
               what finally drops the pages. */
            function applyStructure() {
                var s = structure(), paged = hasPages(s), sectioned = hasSections(s);

                shell.querySelectorAll('[data-needs]').forEach(function (el) {
                    var need = el.getAttribute('data-needs');

                    el.hidden = need === 'pages'    ? !paged
                              : need === 'sections' ? !sectioned
                              /* "flat": the plain Questions bar */ : sectioned;
                });

                shell.classList.toggle('is-paged', paged);
                shell.classList.toggle('is-sectioned', sectioned);

                // A sectioned form needs somewhere to put a question on every
                // page, even one the admin has only just added.
                if (sectioned) {
                    pageCards().forEach(function (page) {
                        if (sectionsOf(page).length === 0) addSection(page, true);
                    });
                }

                showPages();
                renderTabs();
                refresh();
            }

            /* One page at a time when the form is paged; all of them, stacked,
               when it is not — otherwise questions on page 2 of a form just
               switched to plain would be on screen nowhere. */
            function showPages() {
                var paged = hasPages(structure()),
                    list  = pageCards();

                if (active >= list.length) active = Math.max(0, list.length - 1);

                list.forEach(function (page, i) {
                    page.hidden = paged ? i !== active : false;
                });
            }

            /* ---- The step tabs ------------------------------------------------
               Built from the pages rather than kept alongside them, so the two
               cannot drift out of step. */
            function renderTabs() {
                if (!tabsBox) return;

                tabsBox.innerHTML = '';

                if (!hasPages(structure())) return;

                pageCards().forEach(function (page, i) {
                    var input = page.querySelector('[data-page-title]'),
                        title = input ? input.value.trim() : '',
                        tab   = document.createElement('button');

                    tab.type      = 'button';
                    tab.className = 'fb-tab' + (i === active ? ' is-active' : '');
                    tab.setAttribute('data-page-tab', i);
                    tab.setAttribute('draggable', 'false');
                    tab.innerHTML = '<span class="fb-tab__n"></span><span class="fb-tab__t"></span>';

                    // textContent, not innerHTML: the title is whatever the
                    // admin typed, and it is not markup.
                    tab.querySelector('.fb-tab__n').textContent = 'Page ' + (i + 1);
                    tab.querySelector('.fb-tab__t').textContent = title || 'Untitled';

                    tabsBox.appendChild(tab);
                });
            }

            /* ---- Numbering and counts ----------------------------------------
               Questions are numbered straight through the whole form rather than
               restarting on each page, because that is the order the person
               filling it in will meet them. */
            function refresh() {
                pageCards().forEach(function (page, i) {
                    var badge = page.querySelector('[data-page-number]');
                    if (badge) badge.textContent = (i + 1);

                    sectionsOf(page).forEach(function (section) {
                        var count = section.querySelectorAll('[data-row]').length,
                            label = section.querySelector('[data-section-count]');

                        if (label) label.textContent = count + (count === 1 ? ' question' : ' questions');
                    });
                });

                allRows().forEach(function (row, i) {
                    var index = row.querySelector('[data-row-index]');
                    if (index) index.textContent = (i + 1);
                });
            }

            /* ---- Where every question ended up --------------------------------
               Read off the DOM and written into each row just before the form is
               posted. Doing it here rather than on every drag is what lets a
               question be dragged between sections without renaming a single
               input. */
            function stamp() {
                pageCards().forEach(function (page) {
                    var pageRef = page.getAttribute('data-page-ref');

                    sectionsOf(page).forEach(function (section) {
                        var sectionRef = section.getAttribute('data-section-ref'),
                            owner      = section.querySelector('[data-section-page]');

                        if (owner) owner.value = pageRef;

                        section.querySelectorAll('[data-row]').forEach(function (row) {
                            var p = row.querySelector('[data-row-page]'),
                                s = row.querySelector('[data-row-section]');

                            if (p) p.value = pageRef;
                            if (s) s.value = sectionRef;
                        });
                    });
                });
            }

            /* ---- One input for every question ---------------------------------
               PHP reads at most max_input_vars inputs from a request — 1000 here
               and on most hosts — and silently drops the rest. A question row is
               a dozen-odd inputs plus two per option, so a 50-question quiz lost
               its last questions on save with no error at all.

               So just before the form goes, every fields[…] / pages[…] /
               sections[…] input is written into ONE hidden input as the list of
               [name, value] pairs the browser would have sent, in the order it
               would have sent them, and the originals are switched off so they
               are not sent as well. FormBuilderRequest rebuilds the arrays from
               the list by the same rules PHP uses. Order matters: it is how the
               builder stores order.

               Checkboxes and radios only count when ticked, and disabled inputs
               not at all — exactly as a real submission. */
            var packed = form.querySelector('[name="builder_payload"]');

            function pack() {
                var pairs  = [],
                    inputs = form.querySelectorAll('[name^="fields["], [name^="pages["], [name^="sections["]');

                Array.prototype.forEach.call(inputs, function (input) {
                    if (input.disabled) return;
                    if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) return;

                    pairs.push([input.name, input.value]);
                });

                if (!packed) {
                    packed = document.createElement('input');
                    packed.type = 'hidden';
                    packed.name = 'builder_payload';
                    form.appendChild(packed);
                }

                packed.value = JSON.stringify(pairs);

                Array.prototype.forEach.call(inputs, function (input) {
                    if (!input.disabled) {
                        input.disabled = true;
                        input.setAttribute('data-packed', '');
                    }
                });
            }

            // Coming back to this page with the browser's Back button can restore
            // it exactly as it was left — with every input still switched off.
            window.addEventListener('pageshow', function () {
                form.querySelectorAll('[data-packed]').forEach(function (input) {
                    input.disabled = false;
                    input.removeAttribute('data-packed');
                });
            });

            var submitting = false;

            form.addEventListener('submit', function () {
                submitting = true;
                stamp();
                pack();
            });

            /* ---- Per-row: which panels this field type shows -----------------
               Driven entirely off the registry passed in as TYPES, so a field
               type added in PHP gets the right panels here with no change to
               this function. */
            function applyType(row) {
                var input = row.querySelector('[data-row-type]');
                if (!input) return;

                var type = input.value,
                    spec = TYPES[type] || { options: false, validations: [], control: 'input', multiple: false, label: type };

                function show(selector, on) {
                    row.querySelectorAll('[data-when="' + selector + '"]').forEach(function (el) { el.hidden = !on; });
                }

                show('options', spec.options === 'option');
                show('grid', spec.options === 'grid');
                show('tick_grid', spec.control === 'tick_grid');
                show('file', spec.control === 'file');
                show('hidden-note', spec.control === 'hidden');
                // A placeholder means nothing on a radio group, a grid or a
                // file picker.
                show('placeholder', ['input', 'textarea', 'select'].indexOf(spec.control) !== -1);

                ['min_length', 'max_length', 'min_value', 'max_value'].forEach(function (rule) {
                    show(rule, spec.validations.indexOf(rule) !== -1);
                });

                // The type-specific setting blocks, each shown for exactly the
                // type that declares its settings.
                show('scale-block', spec.validations.indexOf('scale_min') !== -1);
                show('rating-block', spec.validations.indexOf('rating_count') !== -1);

                // The extras drawer only opens for a type that has extras. Most
                // questions need nothing but a label and a type, and a row for
                // one of those stays a single line.
                var extra = row.querySelector('[data-row-extra]');
                if (extra) {
                    extra.hidden = ! (
                        spec.options === 'option' ||
                        spec.options === 'grid' ||
                        spec.control === 'file' ||
                        spec.validations.indexOf('scale_min') !== -1 ||
                        spec.validations.indexOf('rating_count') !== -1
                    );
                }

                var buttonLabel = row.querySelector('[data-type-label]'),
                    buttonIcon  = row.querySelector('[data-type-icon]');
                if (buttonLabel) buttonLabel.textContent = spec.label;
                if (buttonIcon && spec.icon) buttonIcon.innerHTML = spec.icon;

                row.querySelectorAll('[data-type-value]').forEach(function (option) {
                    option.setAttribute('aria-selected', option.getAttribute('data-type-value') === type ? 'true' : 'false');
                });

                // Yes/No arrives with its two options ready to relabel rather
                // than as an empty list; a grid arrives empty, because there is
                // no sensible default row or column for a question nobody has
                // written yet.
                if (type === 'yes_no' && optionsOf(row).length === 0) {
                    addOption(row, 'options', 'Yes', 'Yes');
                    addOption(row, 'options', 'No', 'No');
                }

                show('correct', type === CORRECT_TYPE && isQuiz());
                refreshCorrect(row);
                refreshOptionCount(row);
            }

            /* ---- Quiz: the correct answer ------------------------------------
               Only a Multiple choice question on a Quiz shows the picker, but
               every one posts it — switching a quiz to a standard form and back
               must not lose the answers in between. */
            var CORRECT_TYPE = @json(\App\Support\FormFieldType::RADIO);

            function isQuiz() {
                var picked = form.querySelector('[data-form-type]:checked');
                return !!picked && picked.value === 'quiz';
            }

            /* The picker lists this question's own options, rebuilt whenever they
               change, so it can never name one that does not exist. Its value is
               an option's stored value — the thing a response stores — falling
               back to the label for an option left without one, as the server
               does.

               The answer is remembered as WHICH OPTION it is (the option row's
               own key), not as its wording. Matched by text, correcting a typo
               in the right option cleared the answer, because the old spelling
               no longer existed. Deleting that option does clear it — there is
               then genuinely nothing to point at, and guessing would be wrong.
               The wording is used only on first build, when all there is to go
               on is the value the server stored. */
            function optionKey(option) {
                var input = option.querySelector('[data-option-label]'),
                    match = input && input.name.match(/\[options\]\[([^\]]+)\]\[label\]$/);

                return match ? match[1] : '';
            }

            function refreshCorrect(row) {
                var select = row.querySelector('[data-correct]');
                if (!select) return;

                var key    = select.getAttribute('data-selected-key') || '',
                    wanted = select.getAttribute('data-selected') || '',
                    found  = null;

                while (select.options.length > 1) select.remove(1);

                optionsOf(row).forEach(function (option) {
                    var inputs = option.querySelectorAll('input[type="text"]'),
                        label  = (inputs[0] ? inputs[0].value : '').trim(),
                        value  = (inputs[1] ? inputs[1].value : '').trim() || label,
                        own    = optionKey(option);

                    if (!label) return;

                    var item = new Option(label, value);
                    item.setAttribute('data-key', own);

                    if (!found && (key ? own === key : (wanted !== '' && (value === wanted || label === wanted)))) {
                        item.selected = true;
                        found = item;
                    }

                    select.add(item);
                });

                if (!found) select.value = '';
                rememberCorrect(select);
            }

            /* Record the picked option by key and by value, after any change. */
            function rememberCorrect(select) {
                var picked = select.selectedIndex > 0 ? select.options[select.selectedIndex] : null;

                if (picked) {
                    select.setAttribute('data-selected-key', picked.getAttribute('data-key') || '');
                    select.setAttribute('data-selected', picked.value);
                } else {
                    select.removeAttribute('data-selected-key');
                    select.setAttribute('data-selected', '');
                }
            }

            /* ---- The field-type dropdown ------------------------------------
               A listbox rather than a <select>, because the menu carries an icon
               per type and headings that cannot be selected. Delegated from the
               shell, so a cloned row needs nothing re-bound. */
            function closeTypeMenus(except) {
                shell.querySelectorAll('[data-type-menu]').forEach(function (menu) {
                    if (menu === except) return;
                    menu.hidden = true;
                    var button = menu.parentNode.querySelector('[data-type-toggle]');
                    if (button) button.setAttribute('aria-expanded', 'false');
                });
            }

            document.addEventListener('click', function (e) {
                if (!e.target.closest('[data-type-picker]')) closeTypeMenus(null);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeTypeMenus(null);
            });

            /* ---- The key a row's inputs are named under ---------------------- */
            function rowKey(row) {
                var input = row.querySelector('[name^="fields["]');
                var match = input && input.name.match(/^fields\[([^\]]+)\]/);
                return match ? match[1] : null;
            }

            /* ---- Options, grid rows and grid columns -------------------------
               One set of helpers for all three lists: they are the same shape
               (label, value, order) and the same partial renders them, so
               `list` is the only thing that differs. */
            function listBox(row, list) {
                return list === 'options'
                    ? row.querySelector('[data-options]')
                    : row.querySelector('[data-grid="' + list + '"]');
            }

            function itemsOf(row, list) {
                var host = listBox(row, list);
                return host ? Array.prototype.slice.call(host.querySelectorAll('[data-option]')) : [];
            }

            function optionsOf(row) { return itemsOf(row, 'options'); }

            function addOption(row, list, label, value) {
                var host = listBox(row, list);
                if (!host) return;

                var key  = rowKey(row),
                    seed = 'x' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
                    html = optionTpl.innerHTML
                        .replace(/__I__/g, key)
                        .replace(/__O__/g, seed)
                        // The template renders as an "options" row; this is what
                        // makes the same markup post as rows[] or columns[].
                        .replace(/\]\[options\]\[/g, '][' + list + '][')
                        .replace(/data-list="options"/g, 'data-list="' + list + '"');

                host.insertAdjacentHTML('beforeend', html);

                var added = host.lastElementChild;
                if (label) added.querySelector('[data-option-label]').value = label;
                if (value) added.querySelectorAll('input[type="text"]')[1].value = value;

                // Not while importing: focusing each new option scrolls the page
                // to it, which across fifty questions is the page jumping fifty
                // times.
                if (!importing) added.querySelector('[data-option-label]').focus();
                refreshCorrect(row);
                refreshOptionCount(row);
            }

            function refreshOptionCount(row) {
                var spec = TYPES[row.querySelector('[data-row-type]').value] || {};

                ['options', 'rows', 'columns'].forEach(function (list) {
                    var none = list === 'options'
                        ? row.querySelector('[data-options-empty]')
                        : row.querySelector('[data-grid-empty="' + list + '"]');
                    if (none) none.hidden = itemsOf(row, list).length > 0;
                });

                var badge = row.querySelector('[data-row-optcount]');
                if (!badge) return;

                if (spec.options === 'option') {
                    var count = optionsOf(row).length;
                    badge.textContent = count ? count + ' option' + (count === 1 ? '' : 's') : '';
                } else if (spec.options === 'grid') {
                    badge.textContent = itemsOf(row, 'rows').length + ' × ' + itemsOf(row, 'columns').length;
                } else {
                    badge.textContent = '';
                }
            }

            function slugKey(label) {
                return (label || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            /* ---- Adding questions, sections and pages ------------------------ */
            function addField(section, type) {
                if (allRows().length >= MAX) {
                    window.alert('A form may hold at most ' + MAX + ' questions.');
                    return null;
                }

                var host = section.querySelector('[data-fields]');
                if (!host) return null;

                // The refs are left blank: stamp() fills them from the DOM the
                // moment before the form is posted, which is the only time they
                // are guaranteed to be right.
                host.insertAdjacentHTML('beforeend', fieldTpl.innerHTML
                    .replace(/__I__/g, ref('f'))
                    .replace(/__P__/g, '')
                    .replace(/__S__/g, ''));

                var row = host.lastElementChild;
                row.querySelector('[data-row-type]').value = type;
                closeTypeMenus(null);
                applyType(row);

                // An import adds many and renumbers once at the end.
                if (!importing) {
                    refresh();
                    row.querySelector('[data-row-label]').focus();
                }

                return row;
            }

            function addSection(page, quiet) {
                var host = page.querySelector('[data-sections]');
                if (!host) return null;

                if (sectionsOf(page).length >= MAX_SECTIONS) {
                    window.alert('A page may hold at most ' + MAX_SECTIONS + ' sections.');
                    return null;
                }

                host.insertAdjacentHTML('beforeend', sectionTpl.innerHTML
                    .replace(/__S__/g, ref('s'))
                    .replace(/__P__/g, page.getAttribute('data-page-ref')));

                var section = host.lastElementChild;

                // applyStructure calls this back when a page has no section at
                // all, so a quiet add must not call it again.
                if (!quiet) {
                    applyStructure();
                    section.querySelector('[data-section-title]').focus();
                }

                return section;
            }

            function addPage() {
                if (pageCards().length >= MAX_PAGES) {
                    window.alert('A form may hold at most ' + MAX_PAGES + ' pages.');
                    return null;
                }

                pagesBox.insertAdjacentHTML('beforeend', pageTpl.innerHTML
                    .replace(/__P__/g, ref('p'))
                    .replace(/__S__/g, ref('s')));

                var page = pagesBox.lastElementChild;

                // An import builds its pages and then settles the screen once.
                if (importing) return page;

                // Land on what was just made, rather than leaving the admin on
                // the page they were already looking at.
                active = pageCards().length - 1;
                applyStructure();

                var title = page.querySelector('[data-page-title]');
                if (title) title.focus();

                return page;
            }

            /* ---- Clicks, everywhere in the shell ----------------------------- */
            shell.addEventListener('click', function (e) {
                var page    = e.target.closest('[data-page]'),
                    section = e.target.closest('[data-section]'),
                    row     = e.target.closest('[data-row]');

                /* ---- Pages ---- */
                if (e.target.closest('[data-page-add]')) { addPage(); return; }

                if (e.target.closest('[data-page-remove]') && page) {
                    var onPage = page.querySelectorAll('[data-row]').length;

                    if (pageCards().length === 1) {
                        window.alert('A form needs at least one page.');
                        return;
                    }

                    if (window.confirm(onPage
                        ? 'Delete this page and the ' + onPage + ' question(s) on it? Answers already collected are kept and stay visible on each response.'
                        : 'Delete this page?')) {
                        var index = pageCards().indexOf(page);
                        page.remove();
                        active = Math.max(0, index - 1);
                        applyStructure();
                    }
                    return;
                }

                /* ---- Sections ---- */
                if (e.target.closest('[data-section-add]') && page) { addSection(page); return; }

                if (e.target.closest('[data-section-remove]') && section) {
                    var inSection = section.querySelectorAll('[data-row]').length;

                    if (window.confirm(inSection
                        ? 'Delete this section and the ' + inSection + ' question(s) in it? Answers already collected are kept and stay visible on each response.'
                        : 'Delete this section?')) {
                        section.remove();
                        applyStructure();
                    }
                    return;
                }

                if (e.target.closest('[data-section-toggle]') && section) {
                    var body = section.querySelector('[data-section-body]'),
                        shut = section.classList.toggle('is-collapsed');

                    if (body) body.hidden = shut;
                    e.target.closest('[data-section-toggle]').setAttribute(
                        'aria-label', shut ? 'Expand section' : 'Collapse section',
                    );
                    return;
                }

                /* ---- Questions ---- */
                if (e.target.closest('[data-field-add]') && section) { addField(section, 'short_text'); return; }

                if (!row) return;

                if (e.target.closest('[data-row-remove]')) {
                    // A field with answers behind it is soft-deleted on save, not
                    // destroyed — the warning says so rather than overstating it.
                    if (window.confirm('Remove this field? Answers already collected for it are kept and stay visible on each response.')) {
                        row.remove();
                        refresh();
                    }
                    return;
                }

                /* ---- The field-type dropdown ---- */
                var toggle = e.target.closest('[data-type-toggle]');

                if (toggle) {
                    var menu = toggle.parentNode.querySelector('[data-type-menu]'),
                        open = menu.hidden;
                    closeTypeMenus(open ? menu : null);
                    menu.hidden = !open;
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    return;
                }

                var choice = e.target.closest('[data-type-value]');

                if (choice) {
                    row.querySelector('[data-row-type]').value = choice.getAttribute('data-type-value');
                    closeTypeMenus(null);
                    applyType(row);
                    refresh();
                    return;
                }

                if (e.target.closest('[data-option-add]')) { addOption(row, 'options'); return; }

                var gridAdd = e.target.closest('[data-grid-add]');
                if (gridAdd) { addOption(row, gridAdd.getAttribute('data-grid-add')); return; }

                if (e.target.closest('[data-option-remove]')) {
                    e.target.closest('[data-option]').remove();
                    refreshCorrect(row);
                    refreshOptionCount(row);
                    return;
                }
            });

            shell.addEventListener('change', function (e) {
                if (e.target.matches('[data-row-required]')) refresh();
            });

            shell.addEventListener('input', function (e) {
                if (e.target.matches('[data-page-title]')) { renderTabs(); return; }
                if (e.target.matches('[data-row-label]')) { refresh(); return; }

                if (e.target.closest('[data-options] [data-option]')) {
                    refreshCorrect(e.target.closest('[data-row]'));
                }
            });

            // The picker's own choice, kept where a rebuild can find it.
            shell.addEventListener('change', function (e) {
                if (e.target.matches('[data-correct]')) rememberCorrect(e.target);
            });

            /* ---- Switching structure ------------------------------------------ */
            form.querySelectorAll('[data-structure]').forEach(function (radio) {
                radio.addEventListener('change', applyStructure);
            });

            /* ---- Switching Standard / Quiz ------------------------------------ */
            form.querySelectorAll('[data-form-type]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    allRows().forEach(function (row) {
                        var type = row.querySelector('[data-row-type]');

                        row.querySelectorAll('[data-when="correct"]').forEach(function (el) {
                            el.hidden = !(type && type.value === CORRECT_TYPE && isQuiz());
                        });
                    });
                });
            });

            /* ---- The step tabs: switch page, and drag to reorder --------------- */
            if (tabsBox) {
                tabsBox.addEventListener('click', function (e) {
                    var tab = e.target.closest('[data-page-tab]');
                    if (!tab) return;

                    active = parseInt(tab.getAttribute('data-page-tab'), 10) || 0;
                    showPages();
                    renderTabs();
                });

                // The tabs are what reorder the pages. Dragging the page cards
                // themselves would mean dragging a panel the height of the
                // screen past the one next to it.
                var tabFrom = null;

                tabsBox.addEventListener('mousedown', function (e) {
                    var tab = e.target.closest('[data-page-tab]');
                    if (tab) tab.setAttribute('draggable', 'true');
                });

                tabsBox.addEventListener('dragstart', function (e) {
                    var tab = e.target.closest('[data-page-tab]');
                    if (!tab) return;

                    tabFrom = parseInt(tab.getAttribute('data-page-tab'), 10);
                    tab.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', '');
                });

                tabsBox.addEventListener('dragover', function (e) {
                    // Either a tab being reordered, or a question being carried
                    // onto another page.
                    if (tabFrom === null && !(dragging && mode === 'row')) return;

                    var tab = e.target.closest('[data-page-tab]');
                    if (tab) tab.classList.add('is-target');

                    e.preventDefault();
                });

                tabsBox.addEventListener('dragleave', function (e) {
                    var tab = e.target.closest('[data-page-tab]');
                    if (tab) tab.classList.remove('is-target');
                });

                tabsBox.addEventListener('drop', function (e) {
                    var tab = e.target.closest('[data-page-tab]');
                    if (!tab) return;

                    e.preventDefault();
                    tab.classList.remove('is-target');

                    var to   = parseInt(tab.getAttribute('data-page-tab'), 10),
                        list = pageCards();

                    /* A question dropped on a tab moves to that page.
                       Only one page is on screen at a time, so there is no way
                       to drag a question from page one to page three — and
                       without this there would be no way to move it at all,
                       which is a poor answer on a form the admin is still
                       working out the shape of. The tabs are already there and
                       already say which page is which. */
                    if (dragging && mode === 'row') {
                        var into = list[to] && list[to].querySelector('[data-fields]');

                        if (into && !into.contains(dragging)) {
                            into.appendChild(dragging);
                            active = to;
                            applyStructure();
                        }

                        return;
                    }

                    if (tabFrom === null) return;

                    var moved = list[tabFrom];

                    if (moved && to !== tabFrom) {
                        to > tabFrom ? list[to].after(moved) : list[to].before(moved);
                        active = to;
                    }

                    tabFrom = null;
                    applyStructure();
                });

                tabsBox.addEventListener('dragend', function () {
                    tabFrom = null;
                    tabsBox.querySelectorAll('[data-page-tab]').forEach(function (tab) {
                        tab.classList.remove('is-dragging');
                        tab.setAttribute('draggable', 'false');
                    });
                });
            }

            /* ---- Drag and drop ------------------------------------------------
               Three things reorder by dragging: a section, a question, and a
               choice within a question. All three use DOM order as the stored
               order — a form posts its inputs in document order, so moving an
               element is the whole of it.

               A grip makes its element draggable only while it is held. Without
               that, everything would be draggable all the time and text inside
               the inputs could not be selected.

               The grips are checked innermost FIRST: an option lives inside a
               question which lives inside a section, so a grip inside one of
               them would otherwise pick up its container and drag the lot. */
            var dragging = null,
                mode     = null;   // 'option' | 'row' | 'section'

            function clearDraggable() {
                shell.querySelectorAll('[data-row], [data-option], [data-section]').forEach(function (el) {
                    el.setAttribute('draggable', 'false');
                });
            }

            shell.addEventListener('mousedown', function (e) {
                var optionGrip = e.target.closest('[data-option-grip]');

                if (optionGrip) {
                    optionGrip.closest('[data-option]').setAttribute('draggable', 'true');
                    return;
                }

                var rowGrip = e.target.closest('[data-row-grip]');

                if (rowGrip) {
                    rowGrip.closest('[data-row]').setAttribute('draggable', 'true');
                    return;
                }

                var sectionGrip = e.target.closest('[data-section-grip]');
                if (sectionGrip) sectionGrip.closest('[data-section]').setAttribute('draggable', 'true');
            });

            shell.addEventListener('mouseup', clearDraggable);

            shell.addEventListener('dragstart', function (e) {
                var held = function (selector) {
                    var el = e.target.closest(selector);
                    return el && el.getAttribute('draggable') === 'true' ? el : null;
                };

                dragging = held('[data-option]');
                mode     = dragging ? 'option' : null;

                if (!dragging) { dragging = held('[data-row]'); mode = dragging ? 'row' : null; }
                if (!dragging) { dragging = held('[data-section]'); mode = dragging ? 'section' : null; }

                if (dragging) {
                    dragging.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    // Firefox will not start a drag without data on the transfer.
                    e.dataTransfer.setData('text/plain', '');
                }
            });

            shell.addEventListener('dragover', function (e) {
                if (!dragging) return;
                e.preventDefault();

                var selector = mode === 'option' ? '[data-option]' : mode === 'section' ? '[data-section]' : '[data-row]',
                    over     = e.target.closest(selector);

                if (!over || over === dragging) {
                    // Dropping a question into a section that has none yet:
                    // there is no sibling row to aim at, so anywhere in that
                    // section counts. Without this a new section could never be
                    // filled by dragging into it.
                    if (mode === 'row') {
                        var target  = e.target.closest('[data-section]'),
                            host    = target && target.querySelector('[data-fields]');

                        if (host && !host.contains(dragging)) host.appendChild(dragging);
                    }
                    return;
                }

                // A choice may only move within its own list: an option cannot
                // become a grid column, and a row of one question cannot end up
                // under another. A section may not be dropped inside itself.
                if (mode === 'option' && over.parentNode !== dragging.parentNode) return;
                if (mode === 'section' && dragging.contains(over)) return;

                var rect  = over.getBoundingClientRect(),
                    after = e.clientY > rect.top + rect.height / 2;

                after ? over.after(dragging) : over.before(dragging);
            });

            shell.addEventListener('dragend', function () {
                if (dragging) dragging.classList.remove('is-dragging');
                dragging = null;
                mode     = null;
                clearDraggable();
                refresh();
            });

            /* ---- The link preview under the name -----------------------------
               A hint while typing, not a promise: the address is confirmed by
               the server when Generate Link is pressed. */
            var nameInput   = document.getElementById('name'),
                linkPreview = document.querySelector('[data-link-preview]'),
                base        = @json(url('/forms')) + '/';

            if (nameInput && linkPreview) {
                nameInput.addEventListener('input', function () {
                    // The path only — see the markup for why the absolute URL is
                    // not shown here.
                    linkPreview.textContent = '/forms/' + (slugKey(nameInput.value) || '…');
                });
            }

            /* ---- Generate Link → Create Form ---------------------------------
               Generate Link asks the server for the address this form would get
               and shows it; Create Form inside that dialog is what actually
               saves. The slug comes from the server rather than being guessed
               here, because only it knows whether the name is already taken —
               and showing an admin a link that turns out to be
               "course-enquiry-2" would be showing them the wrong link. */
            var generate = document.getElementById('generateLink'),
                dialog   = document.getElementById('linkDialog');

            if (generate && dialog) {
                var linkField = document.getElementById('generatedLink'),
                    createBtn = document.getElementById('createFormBtn'),
                    cancelBtn = document.getElementById('linkDialogCancel');

                function openDialog() {
                    dialog.classList.add('is-open');
                    requestAnimationFrame(function () { dialog.classList.add('is-visible'); });
                    createBtn.focus();
                }

                function closeDialog() {
                    dialog.classList.remove('is-visible');
                    setTimeout(function () { dialog.classList.remove('is-open'); }, 180);
                    generate.focus();
                }

                generate.addEventListener('click', function () {
                    var name = (nameInput.value || '').trim();

                    // The two things a form cannot be created without, checked
                    // before the admin is shown a link for it.
                    if (!name) {
                        if (window.hmToast) window.hmToast('Give the form a name first.', 'warning');
                        nameInput.focus();
                        return;
                    }

                    if (allRows().length === 0) {
                        if (window.hmToast) window.hmToast('Add at least one question first.', 'warning');
                        return;
                    }

                    generate.disabled = true;

                    fetch(@json(route('backend.forms.slug-preview')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({ name: name })
                    })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                        .then(function (data) {
                            linkField.value = data.url;
                            openDialog();
                        })
                        .catch(function () {
                            // The link is a courtesy; falling back to a guess
                            // beats refusing to let the admin continue.
                            linkField.value = base + slugKey(name);
                            openDialog();
                        })
                        .then(function () { generate.disabled = false; });
                });

                createBtn.addEventListener('click', function () {
                    createBtn.disabled = true;
                    createBtn.textContent = 'Creating…';

                    // form.submit() does not fire the submit event, so the refs
                    // have to be written here by hand. Without this every
                    // question on a form created through this dialog would land
                    // on page one with no section.
                    submitting = true;
                    stamp();
                    pack();
                    form.submit();
                });

                cancelBtn.addEventListener('click', closeDialog);

                dialog.addEventListener('click', function (e) {
                    if (e.target === dialog) closeDialog();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && dialog.classList.contains('is-open')) closeDialog();
                });
            }

            /* ---- Copy, inside the dialog ------------------------------------- */
            document.querySelectorAll('[data-copy]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = document.querySelector(button.getAttribute('data-copy'));
                    if (!target) return;

                    target.select();
                    target.setSelectionRange(0, 99999);

                    var done = function () {
                        var original = button.textContent;
                        button.textContent = 'Copied';
                        setTimeout(function () { button.textContent = original; }, 1600);
                    };

                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(target.value).then(done, function () {
                            document.execCommand('copy');
                            done();
                        });
                    } else {
                        document.execCommand('copy');
                        done();
                    }
                });
            });

            /* ---- Bulk upload ----------------------------------------------------
               Choose a sheet → the server reads and checks it → the admin looks
               over every row → Import lays the questions into the builder.

               The server never writes. It answers with the checked questions,
               in order, and they are added here — to the section whose Bulk
               Upload button was pressed — with the very functions + Add Question
               uses. Pages and sections are never made by a sheet; they are made
               by hand. The questions are then saved with the form, through
               the same request, validation and transaction as any question an
               admin types. That is what makes an imported question
               indistinguishable from a typed one.

               Everything from the sheet is rendered with textContent. A cell is
               whatever someone typed into a spreadsheet, and it is not markup. */
            var bulk = document.getElementById('bulkDialog');

            if (bulk) (function () {
                var q = function (selector) { return bulk.querySelector(selector); };

                var fileInput = q('[data-bulk-file]'),
                    drop      = q('[data-bulk-drop]'),
                    chip      = q('[data-bulk-chip]'),
                    errorBox  = q('[data-bulk-error]'),
                    importBtn = q('[data-bulk-import]'),
                    backBtn   = q('[data-bulk-back]'),
                    stages    = {
                        choose: q('[data-bulk-stage="choose"]'),
                        review: q('[data-bulk-stage="review"]')
                    },
                    URL_PREVIEW = @json(route('backend.forms.bulk-preview')),
                    MAX_BYTES   = {{ \App\Support\FormImportSheet::MAX_KB }} * 1024,
                    opener  = null,   // the section Bulk Upload was pressed in — every question goes here
                    context = null,   // the builder as it was when the sheet was checked
                    fields  = [],
                    chosen  = null,
                    request = null;

                function stage(name) {
                    Object.keys(stages).forEach(function (key) { stages[key].hidden = key !== name; });
                    backBtn.hidden   = name !== 'review';
                    importBtn.hidden = name !== 'review';
                }

                function kb(bytes) {
                    return bytes < 1024 * 1024
                        ? Math.max(1, Math.round(bytes / 1024)) + ' KB'
                        : (bytes / 1024 / 1024).toFixed(1) + ' MB';
                }

                /* What the server needs to check against: whether this is a quiz
                   (which needs its correct answers) and how many questions are
                   already on the form. Nothing about pages or sections — those
                   are made by hand, and a sheet only ever fills the section its
                   button was pressed in. */
                function snapshot() {
                    return {
                        form_type: isQuiz() ? 'quiz' : 'standard',
                        questions: allRows().length
                    };
                }

                /* One sentence saying where the questions will go, so nobody has
                   to guess what "Bulk Upload" in the third section means. */
                function describeTarget() {
                    var s     = structure(),
                        page  = opener.closest('[data-page]'),
                        pages = pageCards(),
                        pt    = page.querySelector('[data-page-title]'),
                        st    = opener.querySelector('[data-section-title]'),
                        pName = (pt && pt.value.trim()) || ('Page ' + (pages.indexOf(page) + 1)),
                        sName = (st && st.value.trim()) || ('Section ' + (sectionsOf(page).indexOf(opener) + 1));

                    if (s === 'sections')       return 'Questions will be added to “' + sName + '”, after the ones already there.';
                    if (s === 'pages')          return 'Questions will be added to “' + pName + '”, after the ones already there.';
                    if (s === 'pages_sections') return 'Questions will be added to “' + pName + ' › ' + sName + '”, after the ones already there.';

                    return 'Questions will be added after the ones already on this form.';
                }

                /* The template for the form type picked right now. A quiz gets
                   its own: Question instead of Label, Correct Answer, and no
                   Placeholder. */
                function pointTemplate() {
                    var link = q('[data-bulk-template]'),
                        hint = q('[data-bulk-template-hint]'),
                        quiz = isQuiz();

                    link.href = link.getAttribute(quiz ? 'data-quiz' : 'data-standard');
                    link.lastChild.textContent = quiz ? ' Download Quiz Template' : ' Download Excel Template';
                    hint.textContent = quiz
                        ? 'Download the quiz template, fill in each question, its options and the correct answer, then upload it here.'
                        : 'Download the template, fill in your questions and field details, then upload it here.';
                }

                function open(section) {
                    opener = section;
                    reset();
                    pointTemplate();
                    q('[data-bulk-target]').textContent = describeTarget();

                    bulk.classList.add('is-open');
                    requestAnimationFrame(function () { bulk.classList.add('is-visible'); });
                    drop.focus();
                }

                function close() {
                    if (request) request.abort();
                    request = null;

                    bulk.classList.remove('is-visible');
                    setTimeout(function () { bulk.classList.remove('is-open'); }, 180);

                    var button = opener && opener.querySelector('[data-bulk-open]');
                    if (button) button.focus();
                }

                function reset() {
                    if (request) request.abort();

                    request = null;
                    fields  = [];
                    chosen  = null;
                    fileInput.value = '';
                    drop.hidden     = false;
                    chip.hidden     = true;
                    errorBox.hidden = true;
                    importBtn.disabled = true;
                    stage('choose');
                }

                function status(text, tone) {
                    var el = q('[data-bulk-status]');
                    el.textContent = text;
                    el.className   = 'fb-file__status' + (tone ? ' fb-file__status--' + tone : '');
                }

                function fail(message) {
                    status('Could not be used', 'bad');
                    errorBox.textContent = message;
                    errorBox.hidden      = false;
                }

                /* ---- Upload and check ---- */
                function check(file) {
                    chosen = file;
                    drop.hidden     = true;
                    chip.hidden     = false;
                    errorBox.hidden = true;
                    q('[data-bulk-name]').textContent = file.name;
                    q('[data-bulk-size]').textContent = kb(file.size);

                    // The server checks all of this too; saying it here saves a
                    // round trip for the obvious ones.
                    if (!/\.(xlsx|xls)$/i.test(file.name)) return fail('Upload an Excel file — .xlsx or .xls.');
                    if (file.size > MAX_BYTES) return fail('That file is larger than ' + kb(MAX_BYTES) + '.');

                    status('Checking…', 'busy');

                    context = snapshot();

                    var body = new FormData();
                    body.append('file', file);
                    body.append('context', JSON.stringify(context));

                    request = window.AbortController ? new AbortController() : null;

                    fetch(URL_PREVIEW, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
                        },
                        body: body,
                        signal: request ? request.signal : undefined
                    })
                        .then(function (response) {
                            if (response.status === 419) {
                                throw new Error('Your session has expired. Reload the page and try again — your questions on screen are not saved yet.');
                            }

                            return response.json().catch(function () {
                                throw new Error('The file could not be checked. Please try again.');
                            }).then(function (data) {
                                if (!response.ok) throw new Error(data.message || 'The file could not be checked. Please try again.');
                                return data;
                            });
                        })
                        .then(function (data) {
                            request = null;
                            status('Checked', 'ok');
                            review(data);
                        })
                        .catch(function (error) {
                            request = null;
                            if (error.name === 'AbortError') return;
                            fail(error.message);
                        });
                }

                /* ---- The preview ---- */
                function cell(row, text, className) {
                    var td = document.createElement('td');
                    td.textContent = text;
                    if (className) td.className = className;
                    row.appendChild(td);
                    return td;
                }

                function chipOf(text, tone) {
                    var span = document.createElement('span');
                    span.className   = 'fb-chip' + (tone ? ' fb-chip--' + tone : '');
                    span.textContent = text;
                    return span;
                }

                function review(data) {
                    var summary  = data.summary,
                        rows     = q('[data-bulk-rows]'),
                        box      = q('[data-bulk-summary]'),
                        problems = q('[data-bulk-problems]'),
                        quiz     = context.form_type === 'quiz',
                        // A standard form's template has no Correct Answer
                        // column; it is shown only when there is something in it.
                        answers  = quiz || data.rows.some(function (row) { return row.correct_answer; }),
                        columns  = answers ? 8 : 7;

                    fields = summary.importable ? data.fields : [];

                    box.innerHTML = '';

                    var file = document.createElement('p');
                    file.className   = 'fb-bulk__file';
                    file.textContent = chosen.name + ' · ' + kb(chosen.size);
                    box.appendChild(file);

                    var chips = document.createElement('div');
                    chips.className = 'fb-bulk__chips';
                    chips.appendChild(chipOf(summary.total + (summary.total === 1 ? ' field' : ' fields') + ' found'));
                    chips.appendChild(chipOf(summary.valid + ' valid', 'ok'));
                    if (summary.invalid) chips.appendChild(chipOf(summary.invalid + (summary.invalid === 1 ? ' error' : ' errors'), 'bad'));
                    if (summary.warnings) chips.appendChild(chipOf(summary.warnings + (summary.warnings === 1 ? ' warning' : ' warnings'), 'warn'));
                    box.appendChild(chips);

                    // Problems with the file as a whole — more questions than the
                    // form can take.
                    problems.innerHTML = '';
                    (data.errors || []).forEach(function (message) {
                        var li = document.createElement('li');
                        li.textContent = message;
                        problems.appendChild(li);
                    });
                    problems.hidden = !(data.errors || []).length;

                    // A quiz's template calls the column Question.
                    q('[data-bulk-labelcol]').textContent = quiz ? 'Question' : 'Label';
                    q('[data-bulk-correctcol]').hidden    = !answers;
                    rows.innerHTML = '';

                    data.rows.forEach(function (row) {
                        var bad  = row.errors.length > 0,
                            warn = row.warnings.length > 0,
                            tr   = document.createElement('tr'),
                            options = row.grid_rows.length
                                ? row.grid_rows.length + ' × ' + row.grid_columns.length
                                : (row.options.length ? row.options.length + (row.options.length === 1 ? ' option' : ' options') : '—');

                        tr.className = bad ? 'is-invalid' : (warn ? 'is-warn' : '');

                        cell(tr, row.row, 'fb-bulk__num-cell');
                        cell(tr, row.order !== null ? row.order : (row.order_input || '—'));
                        cell(tr, row.label || '—', 'fb-bulk__label-cell');
                        cell(tr, row.type_label || '—');
                        cell(tr, row.required === null ? (row.required_input || '—') : (row.required ? 'Yes' : 'No'));
                        cell(tr, options).title = row.options.join(' | ');
                        if (answers) cell(tr, row.correct_answer || '—');
                        cell(tr, bad ? '✗' : (warn ? '!' : '✓'), 'fb-bulk__status ' + (bad ? 'is-bad' : (warn ? 'is-warn' : 'is-ok')));

                        rows.appendChild(tr);

                        // The reasons, on their own line under the row they are about.
                        if (bad || warn) {
                            var note = document.createElement('tr'),
                                td   = document.createElement('td'),
                                list = document.createElement('ul');

                            note.className = 'fb-bulk__note ' + (bad ? 'is-invalid' : 'is-warn');
                            td.colSpan     = columns;

                            row.errors.concat(row.warnings).forEach(function (message, i) {
                                var li = document.createElement('li');
                                li.textContent = (i < row.errors.length ? 'Row ' + row.row + ': ' : 'Note: ') + message;
                                li.className   = i < row.errors.length ? 'is-bad' : 'is-warn';
                                list.appendChild(li);
                            });

                            td.appendChild(list);
                            note.appendChild(td);
                            rows.appendChild(note);
                        }
                    });

                    importBtn.disabled    = !summary.importable;
                    importBtn.textContent = summary.importable
                        ? 'Import ' + summary.valid + (summary.valid === 1 ? ' Field' : ' Fields')
                        : (summary.invalid ? 'Fix the errors to import' : 'Nothing to import');

                    stage('review');
                    importBtn.focus();
                }

                /* ---- Add the questions ----
                   Every one goes into the section Bulk Upload was opened from,
                   after the questions already there, in the sheet's Order.

                   All or nothing on screen too: if any question cannot be added,
                   the ones that were are taken away again, so the builder is
                   never left holding half a file. */
                function fill(row, field) {
                    row.querySelector('[data-row-label]').value = field.label || '';

                    var required = row.querySelector('[data-row-required]'),
                        holder   = row.querySelector('[data-row-placeholder]'),
                        help     = row.querySelector('[data-row-help]');

                    if (required) required.checked = !!field.is_required;
                    if (holder) holder.value = field.placeholder || '';
                    if (help) help.value = field.help_text || '';

                    (field.options || []).forEach(function (label) { addOption(row, 'options', label, ''); });
                    (field.rows || []).forEach(function (label) { addOption(row, 'rows', label, ''); });
                    (field.columns || []).forEach(function (label) { addOption(row, 'columns', label, ''); });

                    // Set after the options exist, since the picker is built from them.
                    var correct = row.querySelector('[data-correct]');

                    if (correct && field.correct_answer) {
                        correct.value = field.correct_answer;
                        rememberCorrect(correct);
                    }

                    refreshOptionCount(row);
                }

                function run() {
                    if (!fields.length) return;

                    // The dialog covers the builder, so nothing should have
                    // changed underneath it — but the check was made against a
                    // form type and a question count, so make sure they hold.
                    if (JSON.stringify(snapshot()) !== JSON.stringify(context) || !document.body.contains(opener)) {
                        stage('choose');
                        drop.hidden = true;
                        chip.hidden = false;
                        return fail('The form changed while this was open. Upload the file again.');
                    }

                    var added = [];

                    importing = true;

                    try {
                        fields.forEach(function (field) {
                            var row = addField(opener, field.field_type);
                            if (!row) throw new Error('question');

                            added.push(row);
                            fill(row, field);
                        });
                    } catch (error) {
                        added.forEach(function (row) { row.remove(); });
                        importing = false;
                        refresh();

                        stage('choose');
                        drop.hidden = true;
                        chip.hidden = false;
                        return fail('The questions could not be added, so none were. Nothing on your form changed.');
                    } finally {
                        importing = false;
                    }

                    // Settle the screen once, and open the page the first
                    // imported question landed on.
                    applyStructure();

                    if (hasPages(structure()) && added.length) {
                        active = Math.max(0, pageCards().indexOf(added[0].closest('[data-page]')));
                        showPages();
                        renderTabs();
                    }

                    refresh();

                    added.forEach(function (row) { row.classList.add('is-imported'); });
                    setTimeout(function () {
                        added.forEach(function (row) { row.classList.remove('is-imported'); });
                    }, 2600);

                    unsaved(added.length);
                    close();

                    if (added[0]) added[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

                    if (window.hmToast) {
                        window.hmToast(added.length + (added.length === 1 ? ' field' : ' fields') + ' imported successfully. Save the form to keep them.', 'success');
                    }
                }

                /* ---- Wiring ---- */
                shell.addEventListener('click', function (e) {
                    var button = e.target.closest('[data-bulk-open]');
                    if (button) open(button.closest('[data-section]'));
                });

                fileInput.addEventListener('change', function () {
                    if (fileInput.files && fileInput.files[0]) check(fileInput.files[0]);
                });

                ['dragenter', 'dragover'].forEach(function (name) {
                    drop.addEventListener(name, function (e) {
                        e.preventDefault();
                        drop.classList.add('is-over');
                    });
                });

                ['dragleave', 'drop'].forEach(function (name) {
                    drop.addEventListener(name, function (e) {
                        e.preventDefault();
                        drop.classList.remove('is-over');
                    });
                });

                drop.addEventListener('drop', function (e) {
                    var file = e.dataTransfer && e.dataTransfer.files[0];
                    if (file) check(file);
                });

                // A file dropped just outside the zone would otherwise make the
                // browser open it and leave the page — and the unsaved form.
                ['dragover', 'drop'].forEach(function (name) {
                    bulk.addEventListener(name, function (e) { e.preventDefault(); });
                });

                q('[data-bulk-change]').addEventListener('click', reset);
                backBtn.addEventListener('click', reset);
                importBtn.addEventListener('click', run);

                bulk.querySelectorAll('[data-bulk-close]').forEach(function (button) {
                    button.addEventListener('click', close);
                });

                bulk.addEventListener('click', function (e) {
                    if (e.target === bulk) close();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && bulk.classList.contains('is-open')) close();
                });
            })();

            /* ---- Imported but not yet saved -----------------------------------
               An import puts questions on screen, not in the database — the same
               as typing them. Say so where Save is, and stop the tab closing on
               them without a warning. */
            var unsavedBar   = form.querySelector('[data-unsaved]'),
                unsavedCount = 0,
                EDITING      = @json($editing);

            function unsaved(count) {
                unsavedCount += count;

                if (!unsavedBar) return;

                unsavedBar.hidden = false;
                unsavedBar.querySelector('[data-unsaved-text]').textContent =
                    unsavedCount + ' imported question' + (unsavedCount === 1 ? ' is' : 's are') + ' not saved yet. '
                    + (EDITING ? 'Press Save Changes to keep ' : 'Press Generate Link to create the form with ')
                    + (unsavedCount === 1 ? 'it.' : 'them.');
            }

            /* ---- Leaving with unsaved changes ---------------------------------
               WHAT COUNTS AS A CHANGE is decided by comparison, not by listening:
               every input on the form is read into a snapshot when the page has
               finished setting itself up, and "unsaved" means the form no longer
               matches it. That catches everything without a hook on each action
               — typing, a new type from the listbox, an option added or removed,
               a question dragged to another section, a whole bulk import — and
               it lets an admin who changes something back see no warning at all.

               WHERE IT IS ASKED:
                 • a link or a button that would take the page away (the menu,
                   Back to Forms, Cancel, sign out) — the dialog above, with
                   Save / Leave without saving / Stay;
                 • closing the tab, reloading, typing an address, the browser's
                   Back — the browser's own warning. Nothing else is allowed
                   there, and no page may change its wording. */
            var afterSave = form.querySelector('[data-after-save]'),
                leaveBox  = document.getElementById('leaveDialog'),
                baseline  = null,
                leaving   = false,
                pending   = null;   // { href } or { form } — where the admin was going

            function snapshotState() {
                stamp();   // where each question sits counts: a drag is a change

                var parts = [];

                Array.prototype.forEach.call(form.querySelectorAll('input[name], select[name], textarea[name]'), function (input) {
                    if (input.disabled || input.type === 'file') return;
                    if (['_token', '_method', 'builder_payload', 'after_save'].indexOf(input.name) !== -1) return;
                    if ((input.type === 'checkbox' || input.type === 'radio') && !input.checked) return;

                    parts.push(input.name + ' ' + input.value);
                });

                return parts.join('');
            }

            function isDirty() {
                return baseline !== null && !submitting && !leaving && snapshotState() !== baseline;
            }

            function openLeave(target) {
                pending = target;
                leaveBox.classList.add('is-open');
                requestAnimationFrame(function () { leaveBox.classList.add('is-visible'); });
                leaveBox.querySelector('[data-leave-save]').focus();
            }

            function closeLeave() {
                pending = null;
                leaveBox.classList.remove('is-visible');
                setTimeout(function () { leaveBox.classList.remove('is-open'); }, 180);
            }

            function carryOn(target) {
                leaving = true;

                if (target.form) {
                    // The admin already chose; submit() skips the submit event,
                    // so this does not come straight back here.
                    target.form.submit();
                } else {
                    window.location.href = target.href;
                }
            }

            // Links, in the capture phase so this is decided before anything else
            // on the page acts on the click.
            document.addEventListener('click', function (e) {
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                var link = e.target.closest('a[href]');
                if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
                if (link.closest('#leaveDialog, #bulkDialog, #linkDialog')) return;

                var href = link.getAttribute('href') || '';
                if (href === '' || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;

                // A jump within this same page is not leaving it.
                if (link.hash && link.href.split('#')[0] === window.location.href.split('#')[0]) return;

                if (!isDirty()) return;

                e.preventDefault();
                e.stopPropagation();
                openLeave({ href: link.href });
            }, true);

            // Any other form on the page that would navigate — signing out, the
            // header search.
            document.addEventListener('submit', function (e) {
                if (e.target === form || e.target.closest('#bulkDialog') || !isDirty()) return;

                e.preventDefault();
                e.stopPropagation();
                openLeave({ form: e.target });
            }, true);

            window.addEventListener('beforeunload', function (e) {
                if (isDirty()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            if (leaveBox) {
                leaveBox.querySelector('[data-leave-stay]').addEventListener('click', closeLeave);

                leaveBox.querySelector('[data-leave-discard]').addEventListener('click', function () {
                    var target = pending;
                    closeLeave();
                    if (target) carryOn(target);
                });

                leaveBox.querySelector('[data-leave-save]').addEventListener('click', function () {
                    var target = pending;

                    // A new form cannot be created without a name or with nothing
                    // in it — the same two things Generate Link checks — so say
                    // so here instead of sending a save that will come back.
                    if (!EDITING) {
                        var name = document.getElementById('name');

                        if (!name.value.trim()) {
                            closeLeave();
                            if (window.hmToast) window.hmToast('Give the form a name first, then create it.', 'warning');
                            name.focus();
                            return;
                        }

                        if (allRows().length === 0) {
                            closeLeave();
                            if (window.hmToast) window.hmToast('Add at least one question first.', 'warning');
                            return;
                        }
                    }

                    // After the save, on to the page that was clicked. A sign-out
                    // or search is not followed on — the admin lands back here,
                    // saved, and can do it again.
                    afterSave.value = target && target.href ? target.href : '';

                    closeLeave();

                    if (form.requestSubmit) {
                        form.requestSubmit();   // fires submit: stamped and packed as usual
                    } else {
                        submitting = true;
                        stamp();
                        pack();
                        form.submit();
                    }
                });

                leaveBox.addEventListener('click', function (e) {
                    if (e.target === leaveBox) closeLeave();
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && leaveBox.classList.contains('is-open')) closeLeave();
                });
            }

            /* ---- Start-up ---------------------------------------------------- */
            allRows().forEach(applyType);
            applyStructure();

            // What "unchanged" looks like — taken last, after the start-up above
            // has filled in anything it fills in by itself (Yes / No's two
            // options, the correct-answer pickers), so that is not a change.
            baseline = snapshotState();
        })();
    </script>
@endpush

@push('styles')
    <style>
        /* ---- The name box, a shade larger than the rest ---- */
        .form-control-hm--lg { font-size: 16px; font-weight: 600; }
        /* A long slug wraps inside the hint instead of running past the column
           edge — `anywhere` because a slug has no spaces to break at. */
        .fb-linkhint {
            color: #A85A2E;
            font-weight: 600;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .form-hint { overflow-wrap: anywhere; }

        /* ---- The + that adds a question ---- */
        .fb-plus {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            margin-left: auto;
            border: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, #A85A2E, #843D21);
            color: #fff;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .fb-plus svg { width: 20px; height: 20px; }
        .fb-plus:hover { transform: scale(1.06); box-shadow: 0 6px 18px rgba(132, 61, 33, .3); }

        /* ---- Question rows ----
           One line each: label on the left, answer type on the right, Required
           beside them. Anything the type needs drops in underneath. */
        .fb-field {
            margin-bottom: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 12px;
            background: #fff;
        }
        .fb-field:focus-within { border-color: #D8C4B2; box-shadow: 0 6px 20px rgba(42, 29, 20, .06); }
        .fb-field.is-dragging { opacity: .45; }

        .fb-field__main { display: flex; align-items: flex-start; gap: 10px; }
        .fb-field__grip { display: flex; padding-top: 13px; color: #B3A69A; cursor: grab; }
        .fb-field__grip svg { width: 18px; height: 18px; }
        .fb-field__index {
            padding-top: 13px;
            min-width: 18px;
            font-size: 12.5px;
            font-weight: 700;
            color: #B3A69A;
        }
        .fb-field__label { flex: 1 1 260px; min-width: 0; }
        .fb-field__type  { flex: 0 1 230px; min-width: 0; }

        .fb-req {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 48px;
            padding: 0 12px;
            flex-shrink: 0;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 10px;
            font-size: 13.5px;
            color: var(--muted, #8A7E70);
            cursor: pointer;
            white-space: nowrap;
        }
        .fb-req input { width: 17px; height: 17px; accent-color: #A85A2E; cursor: pointer; }
        .fb-req:has(input:checked) { border-color: #A85A2E; background: #FDF4EE; color: #843D21; font-weight: 600; }

        .fb-field__tools { display: flex; align-items: center; padding-top: 7px; flex-shrink: 0; }

        /* Delete is the only button on a row now. Sized just under the panel's
           standard 40px icon button — big enough to hit comfortably, quiet
           enough not to be the loudest thing in the line. */
        .fb-del {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            border: 0;
            border-radius: 9px;
            background: none;
            color: #C08A80;
            cursor: pointer;
            transition: color .15s ease, background .15s ease;
        }
        .fb-del svg { width: 19px; height: 19px; }
        .fb-del:hover { color: #C0392B; background: #FBEDEA; }
        .fb-del:focus-visible { outline: 2px solid #C0392B; outline-offset: 1px; }
        .fb-field__extra { margin-top: 14px; padding-top: 14px; border-top: 1px dashed #EDE4DA; }

        /* ---- The link dialog ---- */
        .fb-linkbox { display: flex; gap: 8px; margin: 4px 0 20px; }
        .fb-linkbox input {
            flex: 1;
            min-width: 0;
            padding: 11px 13px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 10px;
            background: #FDFAF7;
            font: inherit;
            font-size: 13.5px;
            color: #843D21;
        }

        /* ---- Sub-panels inside a field ---- */
        .fb-sub { margin-top: 18px; padding-top: 14px; border-top: 1px dashed #EDE4DA; }
        .fb-sub__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .fb-sub__title { margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted, #8A7E70); }

        /* ---- Field-type dropdown ----
           A listbox, because the menu needs an icon per type and headings that
           cannot be selected — neither of which a native <select> can render. */
        .fb-type { position: relative; }
        .fb-type__button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 10px;
            background: #fff;
            font: inherit;
            font-size: 14.5px;
            color: var(--ink, #2E2620);
            text-align: left;
            cursor: pointer;
        }
        .fb-type__button:hover { border-color: #D8C4B2; }
        .fb-type__button[aria-expanded="true"] {
            border-color: #A85A2E;
            box-shadow: 0 0 0 3px rgba(168, 90, 46, .14);
        }
        .fb-type__icon { width: 19px; height: 19px; flex-shrink: 0; color: #A85A2E; }
        .fb-type__button > span { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .fb-type__caret { width: 16px; height: 16px; flex-shrink: 0; color: var(--muted, #8A7E70); }

        .fb-type__menu {
            position: absolute;
            z-index: 40;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            /* Scrollable, because the list is longer than a menu should be tall. */
            max-height: 320px;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 6px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 18px 44px rgba(42, 29, 20, .16);
        }
        .fb-type__group {
            margin: 8px 8px 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--muted, #8A7E70);
        }
        .fb-type__group:first-child { margin-top: 2px; }
        .fb-type__option {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 9px 10px;
            border: 0;
            border-radius: 8px;
            background: none;
            font: inherit;
            font-size: 14px;
            color: var(--ink, #2E2620);
            text-align: left;
            cursor: pointer;
        }
        .fb-type__option:hover { background: #FBF4EE; }
        .fb-type__option svg { width: 18px; height: 18px; flex-shrink: 0; color: var(--muted, #8A7E70); }
        .fb-type__option > span { flex: 1; }
        .fb-type__tick { opacity: 0; color: #A85A2E !important; }
        .fb-type__option[aria-selected="true"] { background: #FDF4EE; font-weight: 600; }
        .fb-type__option[aria-selected="true"] svg { color: #A85A2E; }
        .fb-type__option[aria-selected="true"] .fb-type__tick { opacity: 1; }

        /* ---- Options, grid rows and grid columns ----
           A compact line each. The panel's own .btn-icon is 40px square and
           sizes only .act-ico spans — a raw <svg> inside one renders at its
           intrinsic size, which is what made these controls enormous. */
        .fb-option {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 7px;
        }
        .fb-option__grip { display: flex; flex-shrink: 0; color: #CFC3B6; cursor: grab; }
        .fb-option__grip:hover { color: #A2968A; }
        .fb-option__grip:active { cursor: grabbing; }
        .fb-option__grip svg { width: 15px; height: 15px; }
        .fb-option.is-dragging { opacity: .45; }

        .fb-option__input {
            flex: 1 1 auto;
            min-width: 0;
            height: 40px;
            padding: 8px 12px;
            font-size: 14px;
        }
        /* The stored value is the rarer half, so it takes less of the room. */
        .fb-option__input--value { flex: 0 1 190px; }

        .fb-opt-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 7px;
            background: none;
            color: #A2968A;
            cursor: pointer;
            transition: color .15s ease, background .15s ease;
        }
        .fb-opt-btn svg { width: 15px; height: 15px; }
        .fb-opt-btn:hover { color: #843D21; background: #FBF3EC; }
        .fb-opt-btn--remove:hover { color: #C0392B; background: #FBEDEA; }
        .fb-opt-btn:focus-visible { outline: 2px solid #A85A2E; outline-offset: 1px; }

        /* Adding an option is a small, frequent action — a quiet text button,
           not a filled block competing with Save. */
        .fb-sub__head .btn-soft {
            padding: 6px 12px;
            font-size: 12.5px;
            border-radius: 8px;
        }
        .fb-sub__head .btn-soft svg { width: 14px; height: 14px; }

        /* ---- File-type tick list ---- */
        .fb-checks { display: flex; flex-wrap: wrap; gap: 8px; }
        .fb-check {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
        }
        .fb-check:has(input:checked) { border-color: #A85A2E; background: #FDF4EE; color: #843D21; }

        /* ---- Empty state / add row ---- */
        .fb-add { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
        .fb-add .form-control-hm { max-width: 260px; }

        /* ---- Slug row ---- */
        .fb-slug { display: flex; align-items: center; gap: 0; }
        .fb-slug__prefix {
            padding: 13px 12px;
            border: 1px solid var(--line, #E7DED2);
            border-right: 0;
            border-radius: 10px 0 0 10px;
            background: #FAF6F1;
            font-size: 13.5px;
            color: var(--muted, #8A7E70);
            white-space: nowrap;
        }
        .fb-slug .form-control-hm { border-radius: 0 10px 10px 0; }

        /* ---- Footer actions ---- */
        .fb-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* The one-line row cannot stay one line on a narrow screen: the label,
           the type and Required each take the full width instead. */
        @media (max-width: 991.98px) {
            .fb-field__main { flex-wrap: wrap; }
            .fb-field__label,
            .fb-field__type { flex: 1 1 100%; }
            .fb-req { height: 42px; }
            .fb-field__tools { margin-left: auto; padding-top: 0; }
        }

        @media (max-width: 575.98px) {
            .fb-field__grip { display: none; }
            .fb-option { flex-wrap: wrap; }
            .fb-linkbox { flex-wrap: wrap; }
        }

        /* =================================================================
           STRUCTURE, PAGES AND SECTIONS
           -----------------------------------------------------------------
           Same palette, radii and spacing as the rest of the panel — the
           builder gained a hierarchy, not a second visual language. Nothing
           below restyles an existing class.
           ================================================================= */

        /* ---- Picking the shape ---- */
        .fb-structs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(176px, 1fr));
            gap: 12px;
        }
        .fb-struct { margin: 0; cursor: pointer; }
        /* Off-screen rather than display:none, so the radio stays focusable and
           arrow keys still move through the group. */
        .fb-struct input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }
        .fb-struct__box {
            display: flex;
            flex-direction: column;
            gap: 3px;
            height: 100%;
            padding: 15px 14px;
            border: 1.5px solid var(--line, #E7DED2);
            border-radius: 14px;
            background: #fff;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .fb-struct:hover .fb-struct__box { border-color: #D8C4B2; }
        .fb-struct__icon { width: 26px; height: 26px; margin-bottom: 7px; color: #B3A69A; }
        .fb-struct__name { font-size: 14.5px; font-weight: 700; color: var(--ink, #2E2620); }
        .fb-struct__hint { font-size: 12.5px; line-height: 1.45; color: var(--muted, #8A7E70); }

        .fb-struct input:checked + .fb-struct__box {
            border-color: #A85A2E;
            background: #FDF4EE;
            box-shadow: 0 0 0 3px rgba(168, 90, 46, .12);
        }
        .fb-struct input:checked + .fb-struct__box .fb-struct__icon { color: #A85A2E; }
        .fb-struct input:checked + .fb-struct__box .fb-struct__name { color: #843D21; }
        .fb-struct input:focus-visible + .fb-struct__box { outline: 2px solid #A85A2E; outline-offset: 2px; }

        /* ---- Step tabs ---- */
        /* ---- The bar above the pages: the steps, and Add Page ----
           One row. The tabs take the space and scroll inside it; the button
           keeps its place at the right however many pages there are. */
        .fb-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .fb-tabs {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 0;
            padding-bottom: 4px;
            overflow-x: auto;
        }

        /* Add Section sits in the same place on the page it belongs to, so the
           two controls that give a form its shape read as a pair. */
        .fb-secbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }

        /* ---- Add Page / Add Section ----
           Filled and dark against a screen that is otherwise white on cream:
           these two are what the admin came to this bar to do. Same gradient
           as the round + and the panel's primary buttons. */
        .fb-addbtn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 11px 18px;
            border: 0;
            border-radius: 11px;
            background: linear-gradient(135deg, #A85A2E, #843D21);
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .fb-addbtn svg { width: 17px; height: 17px; }
        .fb-addbtn:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(132, 61, 33, .28); }
        .fb-addbtn:focus-visible { outline: 2px solid #843D21; outline-offset: 2px; }
        .fb-tab {
            display: flex;
            flex-direction: column;
            gap: 1px;
            flex-shrink: 0;
            min-width: 128px;
            max-width: 210px;
            padding: 9px 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 11px;
            background: #fff;
            font: inherit;
            text-align: left;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }
        .fb-tab:hover { border-color: #D8C4B2; }
        .fb-tab__n {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            color: var(--muted, #8A7E70);
        }
        .fb-tab__t {
            overflow: hidden;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ink, #2E2620);
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fb-tab.is-active { border-color: #A85A2E; background: #FDF4EE; }
        .fb-tab.is-active .fb-tab__n { color: #A85A2E; }
        .fb-tab.is-active .fb-tab__t { color: #843D21; }
        .fb-tab.is-dragging { opacity: .45; }
        /* A question being carried onto another page. */
        .fb-tab.is-target {
            border-color: #A85A2E;
            background: #FDF4EE;
            box-shadow: 0 0 0 3px rgba(168, 90, 46, .16);
        }

        /* ---- A page ----
           Only drawn as a panel when the form actually has pages; on a plain or
           sectioned form the page is just a container and must leave no trace. */
        .fb-page { margin-bottom: 14px; }
        .fb-shell.is-paged .fb-page {
            padding: 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 16px;
            background: #FFFDFB;
        }
        .fb-page__head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
        .fb-page__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            margin-top: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, #A85A2E, #843D21);
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }
        .fb-page__titles, .fb-sec__titles { display: flex; flex-direction: column; gap: 2px; flex: 1; min-width: 0; }

        /* Borderless until touched. A builder is mostly reading, and a screen of
           boxed inputs reads as a form to fill in rather than a form to edit. */
        .fb-page__title, .fb-page__desc, .fb-sec__title, .fb-sec__desc {
            width: 100%;
            padding: 6px 9px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: transparent;
            font: inherit;
            color: var(--ink, #2E2620);
        }
        .fb-page__title, .fb-sec__title { font-size: 15.5px; font-weight: 700; }
        .fb-page__desc, .fb-sec__desc { font-size: 13px; color: var(--muted, #8A7E70); }
        .fb-page__title:hover, .fb-page__desc:hover,
        .fb-sec__title:hover, .fb-sec__desc:hover { border-color: #EDE4DA; }
        .fb-page__title:focus, .fb-page__desc:focus,
        .fb-sec__title:focus, .fb-sec__desc:focus {
            border-color: #A85A2E;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(168, 90, 46, .12);
            outline: none;
        }
        .fb-page__title::placeholder, .fb-sec__title::placeholder { font-weight: 600; color: #C4B6A8; }

        /* ---- A section ----
           Also the plain form's question card: on a form with no sections its
           head holds nothing but the "Questions" title and the + button, which
           is what the builder looked like before any of this existed. */
        .fb-sec {
            margin-bottom: 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 16px;
            background: #fff;
        }
        .fb-sec:last-child { margin-bottom: 0; }
        .fb-sec.is-dragging { opacity: .45; }
        .fb-sec__head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid #F1E9DF;
        }
        .fb-shell.is-sectioned .fb-sec__head { align-items: flex-start; }
        .fb-sec.is-collapsed .fb-sec__head { border-bottom: 0; }
        .fb-sec__grip { display: flex; padding-top: 8px; color: #B3A69A; cursor: grab; }
        .fb-sec__grip svg { width: 18px; height: 18px; }
        .fb-sec__plain { flex: 1; margin: 0; }
        .fb-sec__count {
            flex-shrink: 0;
            padding-top: 8px;
            font-size: 12px;
            color: var(--muted, #8A7E70);
            white-space: nowrap;
        }
        .fb-sec__body { padding: 14px; }
        .fb-sec.is-collapsed [data-section-toggle] svg { transform: rotate(-90deg); }

        /* ---- The small square buttons on a page or section head ---- */
        .fb-ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 9px;
            background: none;
            color: var(--muted, #8A7E70);
            cursor: pointer;
            transition: color .15s ease, background .15s ease;
        }
        .fb-ico svg { width: 18px; height: 18px; transition: transform .15s ease; }
        .fb-ico:hover { background: #F6EFE8; color: var(--ink, #2E2620); }
        .fb-ico:focus-visible { outline: 2px solid #A85A2E; outline-offset: 1px; }
        .fb-ico--danger { color: #C08A80; }
        .fb-ico--danger:hover { background: #FBEDEA; color: #C0392B; }



        /* A question list with nothing in it still has to be a drop target, or a
           section just added could never be filled by dragging. */
        .fb-sec__body [data-fields]:empty { min-height: 10px; }

        @media (max-width: 640px) {
            /* Stacked, button first — it stays above the content either way,
               and the tabs keep a full row to scroll in. */
            .fb-bar { flex-direction: column-reverse; align-items: stretch; }
            .fb-addbtn { width: 100%; }
            .fb-sec__head { flex-wrap: wrap; }
            .fb-sec__titles { order: 3; flex-basis: 100%; }
            .fb-sec__plain { flex: 1 1 auto; }
            .fb-page__head { flex-wrap: wrap; }
            .fb-page__titles { order: 3; flex-basis: 100%; }
        }

        /* =================================================================
           BULK UPLOAD, FORM TYPE, CORRECT ANSWER
           -----------------------------------------------------------------
           The panel's own palette, radii and buttons throughout. The modal is
           the admin dialog (.hm-dialog) widened, not a second modal system.
           ================================================================= */

        /* Two cards rather than four, so they do not stretch across the page. */
        .fb-structs--two { grid-template-columns: repeat(auto-fit, minmax(220px, 320px)); }

        /* + Add Question and Bulk Upload, side by side. */
        .fb-addrow { display: flex; flex-wrap: wrap; gap: 10px; }

        /* The correct-answer picker, under a Multiple choice question's options. */
        .fb-correct {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid #EADBC9;
            border-radius: 11px;
            background: #FDF8F2;
        }
        .fb-correct .fb-sub__title { display: block; margin-bottom: 8px; color: #843D21; }
        .fb-correct select { max-width: 420px; }

        /* Questions that have just arrived from a sheet, for a moment. */
        .fb-field.is-imported {
            border-color: #C9A27E;
            box-shadow: 0 0 0 3px rgba(168, 90, 46, .14);
            transition: box-shadow .6s ease, border-color .6s ease;
        }

        .fb-unsaved {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 12px;
            padding: 11px 14px;
            border: 1px solid #F0D9B5;
            border-radius: 11px;
            background: #FFF8EC;
            font-size: 13.5px;
            color: #7A5418;
        }
        .fb-unsaved svg { width: 18px; height: 18px; flex-shrink: 0; }

        /* ---- The modal ---- */
        .hm-dialog__panel.fb-bulk {
            display: flex;
            flex-direction: column;
            width: min(980px, 100%);
            max-height: calc(100vh - 40px);
            padding: 0;
            text-align: left;
            overflow: hidden;
        }
        .fb-bulk__head {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid #F1E9DF;
        }
        .fb-bulk__icon { flex-shrink: 0; width: 44px; height: 44px; margin: 0; box-shadow: 0 0 0 6px #F8F2EC; }
        .fb-bulk__icon svg { width: 20px; height: 20px; }
        .fb-bulk__heading { flex: 1; min-width: 0; }
        .fb-bulk__heading .hm-dialog__title { margin-bottom: 3px; }
        .fb-bulk__sub { margin: 0; font-size: 13.5px; color: var(--muted, #8A7E70); }

        .fb-bulk__body { flex: 1; min-height: 0; padding: 20px 24px; overflow-y: auto; }
        .fb-bulk__target {
            margin: 0 0 18px;
            padding: 10px 13px;
            border-radius: 10px;
            background: #FDF4EE;
            font-size: 13.5px;
            color: #843D21;
        }

        .fb-bulk__step { display: flex; gap: 14px; }
        .fb-bulk__step + .fb-bulk__step { margin-top: 22px; }
        .fb-bulk__num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #A85A2E, #843D21);
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }
        .fb-bulk__stepbody { flex: 1; min-width: 0; }
        .fb-bulk__steptitle { margin: 3px 0 4px; font-size: 15px; font-weight: 700; color: var(--ink, #2E2620); }
        /* The panel's .form-hint is a fixed 500px wide, which is wider than a
           phone: inside this modal it pushed the body into a sideways scroll and
           clipped the sentence. Scoped here rather than changed at the source,
           where other screens are laid out around that width. */
        .fb-bulk .form-hint { width: auto; max-width: 100%; }
        .fb-bulk__stepbody > .form-hint { margin-bottom: 12px; }

        /* The drop zone. A label around a hidden file input, so a click
           anywhere in it — or Enter from the keyboard — opens the picker. */
        .fb-drop {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 28px 20px;
            border: 2px dashed #DCC8B4;
            border-radius: 14px;
            background: #FFFDFB;
            text-align: center;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }
        .fb-drop:hover, .fb-drop:focus-within, .fb-drop.is-over { border-color: #A85A2E; background: #FDF4EE; }
        .fb-drop__input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        .fb-drop__icon { width: 38px; height: 38px; margin-bottom: 4px; color: #A85A2E; }
        .fb-drop__title { font-size: 14.5px; font-weight: 600; color: var(--ink, #2E2620); }
        .fb-drop__or { font-size: 12.5px; color: var(--muted, #8A7E70); }
        .fb-drop__btn { pointer-events: none; }
        .fb-drop__hint { margin-top: 4px; font-size: 12px; color: var(--muted, #8A7E70); }

        .fb-file {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line, #E7DED2);
            border-radius: 12px;
            background: #fff;
        }
        .fb-file__icon { width: 26px; height: 26px; flex-shrink: 0; color: #1F7A4D; }
        .fb-file__meta { display: flex; flex-direction: column; flex: 1; min-width: 0; }
        .fb-file__name { overflow: hidden; font-size: 14px; font-weight: 600; color: var(--ink, #2E2620); text-overflow: ellipsis; white-space: nowrap; }
        .fb-file__size { font-size: 12px; color: var(--muted, #8A7E70); }
        .fb-file__status { flex-shrink: 0; font-size: 12.5px; font-weight: 600; color: var(--muted, #8A7E70); white-space: nowrap; }
        .fb-file__status--busy { color: #A6741F; }
        .fb-file__status--ok { color: #1F7A4D; }
        .fb-file__status--bad { color: #C0392B; }
        .fb-file__change { flex-shrink: 0; }

        .fb-bulk__error {
            margin: 10px 0 0;
            padding: 10px 13px;
            border-radius: 10px;
            background: #FBEDEA;
            font-size: 13.5px;
            color: #A8321F;
        }

        /* ---- The review ---- */
        .fb-bulk__file { margin: 0 0 8px; font-size: 13px; font-weight: 600; color: var(--muted, #8A7E70); }
        .fb-bulk__chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .fb-chip {
            padding: 5px 12px;
            border-radius: 999px;
            background: #F4EDE5;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink, #2E2620);
        }
        .fb-chip--ok { background: #E8F3EC; color: #1F7A4D; }
        .fb-chip--bad { background: #FBEDEA; color: #A8321F; }
        .fb-chip--warn { background: #FFF4DE; color: #8A5A12; }

        .fb-bulk__problems {
            margin: 0 0 14px;
            padding: 10px 14px 10px 30px;
            border-radius: 10px;
            background: #FBEDEA;
            font-size: 13.5px;
            color: #A8321F;
        }

        .fb-bulk__tablewrap { overflow-x: auto; border: 1px solid var(--line, #E7DED2); border-radius: 12px; }
        .fb-bulk__table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .fb-bulk__table th {
            position: sticky;
            top: 0;
            padding: 10px 12px;
            background: #FAF6F1;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .05em;
            text-align: left;
            text-transform: uppercase;
            color: var(--muted, #8A7E70);
            white-space: nowrap;
        }
        .fb-bulk__table td { padding: 9px 12px; border-top: 1px solid #F1E9DF; color: var(--ink, #2E2620); vertical-align: top; }
        .fb-bulk__num-cell { color: var(--muted, #8A7E70); }
        .fb-bulk__label-cell { min-width: 160px; font-weight: 600; }

        .fb-bulk__table tr.is-invalid td { background: #FEF6F4; }
        .fb-bulk__table tr.is-warn td { background: #FFFBF1; }
        .fb-bulk__table tr.fb-bulk__note td { padding-top: 0; border-top: 0; }
        .fb-bulk__note ul { margin: 0; padding-left: 18px; }
        .fb-bulk__note li { font-size: 12.5px; line-height: 1.6; }
        .fb-bulk__note li.is-bad { color: #A8321F; }
        .fb-bulk__note li.is-warn { color: #8A5A12; }

        .fb-bulk__status { font-weight: 700; text-align: center; }
        .fb-bulk__status.is-ok { color: #1F7A4D; }
        .fb-bulk__status.is-bad { color: #C0392B; }
        .fb-bulk__status.is-warn { color: #A6741F; }

        .fb-bulk__foot {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            border-top: 1px solid #F1E9DF;
            background: #FFFDFB;
        }
        .fb-bulk__foot [data-bulk-import] { margin-left: auto; }

        /* ---- Unsaved changes dialog ----
           The confirm dialog's panel, a little wider, with its three answers
           stacked: the one most people want first and loudest, and leaving
           without saving clearly a different kind of choice from staying. */
        .hm-dialog__panel.fb-leave { width: min(440px, 100%); }
        .fb-leave__actions { display: flex; flex-direction: column; gap: 9px; }
        .fb-leave__actions > * { justify-content: center; width: 100%; }
        .fb-leave__discard { color: #B2402C; border-color: #F0D2CA; }
        .fb-leave__discard:hover { background: #FBEDEA; border-color: #E6B7AA; color: #962F1E; }
        .fb-bulk__foot [data-bulk-import]:disabled { opacity: .55; cursor: not-allowed; }

        @media (max-width: 640px) {
            .fb-bulk__head, .fb-bulk__body, .fb-bulk__foot { padding-left: 16px; padding-right: 16px; }
            .fb-file { flex-wrap: wrap; }
            .fb-bulk__foot { flex-wrap: wrap; }
            .fb-bulk__foot [data-bulk-import] { width: 100%; margin-left: 0; }
        }
    </style>
@endpush
