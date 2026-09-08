@extends('backend.template.layouts.template-base')

@php
    $editing = $form->exists;

    /*
     * The field rows to render.
     *
     * old() first, so a failed save gives the admin their work back rather than
     * throwing away a form they spent ten minutes building. Otherwise the saved
     * fields, flattened out of their model/relation shape into the plain arrays
     * the row partial reads — one shape, whichever the source.
     */
    $rows = old('fields');

    if ($rows === null) {
        $rows = $fields->map(fn ($field) => array_merge(
            $field->only(['id', 'field_type', 'label', 'field_key', 'placeholder', 'help_text', 'is_required', 'default_value']),
            (array) $field->validation_rules,
            [
                'multiple'       => $field->setting('multiple'),
                'cond_field_key' => $field->condition()['field_key'] ?? '',
                'cond_operator'  => $field->condition()['operator'] ?? 'equals',
                'cond_value'     => $field->condition()['value'] ?? '',
                'options'        => $field->options->map->only(['label', 'value'])->all(),
                // A grid's two lists. Empty for every other type, and the
                // panels holding them are hidden then anyway.
                'rows'           => $field->rows->map->only(['label', 'value'])->all(),
                'columns'        => $field->columns->map->only(['label', 'value'])->all(),
            ],
        ))->all();

        // Keys the JS counter will not collide with.
        $rows = collect($rows)->mapWithKeys(fn ($row, $i) => ['f' . $i => $row])->all();
    }

    $setting = fn ($key) => old($key, $form->exists ? ($form->settings[$key] ?? null) : \App\Models\Form::SETTING_DEFAULTS[$key]);
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

        {{-- ============================== FIELDS ============================= --}}
        <div class="hm-card mb-3">
            <div class="hm-card__head">
                <h2 class="hm-card__title">Text fields</h2>

                {{-- The + that adds a question. Sits at the right of the heading
                     because that is where the eye goes looking for it. --}}
                <button type="button" class="fb-plus" data-field-add
                        aria-label="Add a question" title="Add a question">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
            <div class="hm-card__body">

                @error('fields') <p class="form-error mb-2">{{ $message }}</p> @enderror

                <div data-fields>
                    @foreach ($rows as $r => $row)
                        @include('backend.forms.partials.field-row', ['r' => $r, 'row' => $row])
                    @endforeach
                </div>

                {{-- A new form starts genuinely empty — no sample questions are
                     inserted, because every question on every form built here is
                     the admin's to decide. There is no empty-state panel: the
                     Add Question button below is right there and says the same
                     thing without a placeholder taking up the space. --}}

                <button type="button" class="btn-soft" data-field-add>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add Question
                </button>

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
         __O__ an option key. Kept out of the form so their inputs never post. --}}
    <template id="fieldTemplate">
        @include('backend.forms.partials.field-row', ['r' => '__I__', 'row' => []])
    </template>
    <template id="optionTemplate">
        @include('backend.forms.partials.option-row', ['r' => '__I__', 'o' => '__O__', 'option' => []])
    </template>

@endsection

@push('scripts')
    <script>
        /* =====================================================================
           Form builder
           ---------------------------------------------------------------------
           Everything the admin does to the field list happens here: add, edit,
           duplicate, delete, reorder, and switching a field's type.

           Two things are worth knowing before changing any of it:

           1. A row's position in the DOM IS its display order. A form posts its
              inputs in document order, so moving a row is all it takes — there
              is no order field to keep in step. Same trick as the event
              repeaters in this panel.

           2. Which settings a field shows is read from the type registry
              (App\Support\FormFieldType), passed in below. Adding a field type
              in PHP makes it appear here with the right panels and no change to
              this script.
           ===================================================================== */
        (function () {
            'use strict';

            var TYPES = @json(\App\Support\FormFieldType::forJavascript());

            var form     = document.getElementById('formBuilder'),
                box      = document.querySelector('[data-fields]'),
                fieldTpl = document.getElementById('fieldTemplate'),
                optionTpl = document.getElementById('optionTemplate'),
                nextRow  = 0,
                MAX      = {{ \App\Services\FormBuilderService::MAX_FIELDS }};

            if (!form || !box) return;

            function rows() { return Array.prototype.slice.call(box.querySelectorAll('[data-row]')); }

            /* ---- The key a row's inputs are named under ---------------------- */
            function rowKey(row) {
                var input = row.querySelector('[name^="fields["]');
                var match = input && input.name.match(/^fields\[([^\]]+)\]/);
                return match ? match[1] : null;
            }

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
               container, so a cloned row needs nothing re-bound. */
            function closeTypeMenus(except) {
                box.querySelectorAll('[data-type-menu]').forEach(function (menu) {
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
                    seq  = 'x' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
                    html = optionTpl.innerHTML
                        .replace(/__I__/g, key)
                        .replace(/__O__/g, seq)
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

            /* ---- Row numbering ------------------------------------------------ */
            function refresh() {
                var list = rows();

                list.forEach(function (row, i) {
                    var index = row.querySelector('[data-row-index]');
                    if (index) index.textContent = (i + 1);
                });
            }

            function slugKey(label) {
                return (label || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }

            /* ---- Add / duplicate --------------------------------------------- */
            function addField(type) {
                if (rows().length >= MAX) {
                    window.alert('A form may hold at most ' + MAX + ' fields.');
                    return null;
                }

                var key = 'n' + (nextRow++);
                box.insertAdjacentHTML('beforeend', fieldTpl.innerHTML.replace(/__I__/g, key));

                var row = box.lastElementChild;
                row.querySelector('[data-row-type]').value = type;
                closeTypeMenus(null);
                applyType(row);
                refresh();
                row.querySelector('[data-row-label]').focus();

                return row;
            }


            /* ---- Events ------------------------------------------------------
               Both + buttons — the one in the card heading and the one under the
               list — add a question. Short answer is where a new one starts; its
               own type dropdown changes it from there. */
            document.querySelectorAll('[data-field-add]').forEach(function (button) {
                button.addEventListener('click', function () { addField('short_text'); });
            });

            box.addEventListener('click', function (e) {
                var row = e.target.closest('[data-row]');
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

            box.addEventListener('change', function (e) {
                var row = e.target.closest('[data-row]');
                if (!row) return;

                if (e.target.matches('[data-row-required]')) refresh();
            });

            box.addEventListener('input', function (e) {
                var row = e.target.closest('[data-row]');
                if (!row) return;

                if (e.target.matches('[data-row-label]')) refresh();
            });

            /* ---- Drag and drop ------------------------------------------------
               Two things reorder by dragging: a question, and a choice within a
               question (its options, or a grid's rows and columns). Both work
               the same way, and both use the DOM order as the stored order — a
               form posts its inputs in document order, so moving an element is
               the whole of it.

               A grip makes its element draggable only while it is held. Without
               that the row would be draggable all the time and text inside its
               inputs could not be selected.

               The option grip is checked FIRST: an option lives inside a
               question, so a grip inside one would otherwise pick up the
               question and drag the lot. */
            var dragging = null,
                draggingOption = false;

            function clearDraggable() {
                box.querySelectorAll('[data-row], [data-option]').forEach(function (el) {
                    el.setAttribute('draggable', 'false');
                });
            }

            box.addEventListener('mousedown', function (e) {
                var optionGrip = e.target.closest('[data-option-grip]');

                if (optionGrip) {
                    optionGrip.closest('[data-option]').setAttribute('draggable', 'true');
                    return;
                }

                var grip = e.target.closest('[data-row-grip]');
                if (grip) grip.closest('[data-row]').setAttribute('draggable', 'true');
            });

            box.addEventListener('mouseup', clearDraggable);

            box.addEventListener('dragstart', function (e) {
                var option = e.target.closest('[data-option]');

                draggingOption = !!(option && option.getAttribute('draggable') === 'true');
                dragging = draggingOption ? option : e.target.closest('[data-row]');

                if (dragging) {
                    dragging.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    // Firefox will not start a drag without data on the transfer.
                    e.dataTransfer.setData('text/plain', '');
                }
            });

            box.addEventListener('dragover', function (e) {
                if (!dragging) return;
                e.preventDefault();

                var over = draggingOption
                    ? e.target.closest('[data-option]')
                    : e.target.closest('[data-row]');

                if (!over || over === dragging) return;

                // A choice may only move within its own list: an option cannot
                // become a grid column, and a row of one question cannot end up
                // under another.
                if (draggingOption && over.parentNode !== dragging.parentNode) return;

                var rect  = over.getBoundingClientRect(),
                    after = e.clientY > rect.top + rect.height / 2;

                after ? over.after(dragging) : over.before(dragging);
            });

            box.addEventListener('dragend', function () {
                if (dragging) dragging.classList.remove('is-dragging');
                dragging = null;
                draggingOption = false;
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

                    if (rows().length === 0) {
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
            rows().forEach(applyType);
            refresh();
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
    </style>
@endpush
