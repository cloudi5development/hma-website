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

            form.addEventListener('submit', stamp);

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

                refreshOptionCount(row);
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

                added.querySelector('[data-option-label]').focus();
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
                refresh();
                row.querySelector('[data-row-label]').focus();

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
                    return;
                }

                pagesBox.insertAdjacentHTML('beforeend', pageTpl.innerHTML
                    .replace(/__P__/g, ref('p'))
                    .replace(/__S__/g, ref('s')));

                // Land on what was just made, rather than leaving the admin on
                // the page they were already looking at.
                active = pageCards().length - 1;
                applyStructure();

                var title = pageCards()[active].querySelector('[data-page-title]');
                if (title) title.focus();
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
                    refreshOptionCount(row);
                    return;
                }
            });

            shell.addEventListener('change', function (e) {
                if (e.target.matches('[data-row-required]')) refresh();
            });

            shell.addEventListener('input', function (e) {
                if (e.target.matches('[data-page-title]')) { renderTabs(); return; }
                if (e.target.matches('[data-row-label]')) refresh();
            });

            /* ---- Switching structure ------------------------------------------ */
            form.querySelectorAll('[data-structure]').forEach(function (radio) {
                radio.addEventListener('change', applyStructure);
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
                    stamp();
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

            /* ---- Start-up ---------------------------------------------------- */
            allRows().forEach(applyType);
            applyStructure();
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
    </style>
@endpush
