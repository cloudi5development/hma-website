{{--
| One question in the builder.
|
| $r  the row's key inside the fields[] array. Opaque — it identifies the row in
|     the POST and in validation error keys, and is NOT the display order. Order
|     is the row's position in the DOM, because a form posts its inputs in
|     document order (the same trick the event repeaters use).
| $row  the row's current values, as a plain array.
|
| Three controls, always: the question, its type, and whether it is required.
| Anything else shown here is a setting the chosen type cannot work without —
| a dropdown needs its options, a grid needs its rows and columns, a scale needs
| its ends. The builder script shows each from the type registry.
--}}
@php
    $type     = $row['field_type'] ?? \App\Support\FormFieldType::SHORT_TEXT;
    $options  = $row['options'] ?? [];
    $errorKey = 'fields.' . $r;
@endphp

<div class="fb-field" data-row draggable="false">

    {{-- --------------------------- THE QUESTION --------------------------- --}}
    <div class="fb-field__main">
        <span class="fb-field__grip" data-row-grip title="Drag to reorder" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/></svg>
        </span>

        <span class="fb-field__index" data-row-index>1</span>

        {{-- The field's own id. Present on an existing question and blank on a
             new one, and it is what stops a save from orphaning the responses
             already filed against it — see FormBuilderService. --}}
        <input type="hidden" name="fields[{{ $r }}][id]" value="{{ $row['id'] ?? '' }}" data-row-id>

        {{-- Where this question sits. Written from the DOM by the builder script
             just before the form is submitted, rather than kept in step on every
             drag: dragging a question into another section would otherwise mean
             rewriting every input name on it. Rendered here as well so the
             values are right even if the script never runs — a question with no
             refs is not lost, it simply lands on the first page. --}}
        <input type="hidden" name="fields[{{ $r }}][page_ref]" value="{{ $pageRef ?? '' }}" data-row-page>
        <input type="hidden" name="fields[{{ $r }}][section_ref]" value="{{ $sectionRef ?? '' }}" data-row-section>

        {{-- Placeholder and help text are not edited on this screen, but they
             are carried through it. The service writes whatever a row posts,
             so a row with no input for them saved them as blank — which went
             unnoticed only while nothing could set them. Bulk upload can. --}}
        <input type="hidden" name="fields[{{ $r }}][placeholder]" value="{{ $row['placeholder'] ?? '' }}" data-row-placeholder>
        <input type="hidden" name="fields[{{ $r }}][help_text]" value="{{ $row['help_text'] ?? '' }}" data-row-help>

        {{-- The same for the rest of what a question can carry but this screen
             does not edit: its show-only-if condition and the limits on its
             answer. Without these every Save erased them — a form's
             conditions, a date window, a file-size cap, gone the first time
             anyone fixed a typo. The service keeps only the settings the
             question's current type offers, so a stale one is dropped when the
             type changes, not before. --}}
        @foreach (['cond_field_key', 'cond_operator', 'cond_value', 'min_length', 'max_length', 'min_value', 'max_value',
                   'max_file_size_kb', 'min_date', 'max_date', 'min_time', 'max_time'] as $carried)
            <input type="hidden" name="fields[{{ $r }}][{{ $carried }}]" value="{{ $row[$carried] ?? '' }}">
        @endforeach

        {{-- Label on the left, type on the right. --}}
        <div class="fb-field__label">
            <input type="text" name="fields[{{ $r }}][label]" data-row-label
                   class="form-control-hm @error($errorKey . '.label') is-invalid @enderror"
                   value="{{ $row['label'] ?? '' }}" placeholder="Type your question">
            @error($errorKey . '.label') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="fb-field__type">
            {{-- A listbox rather than a <select>: the menu carries an icon per
                 type and headings that are not selectable, and a native select
                 can render neither. The real value lives in the hidden input, so
                 the form posts exactly as it would have done.

                 All behaviour is delegated from the fields container in
                 builder.blade.php, so a cloned row works with nothing re-bound. --}}
            <div class="fb-type" data-type-picker>
                <input type="hidden" name="fields[{{ $r }}][field_type]" value="{{ $type }}" data-row-type>

                <button type="button" class="fb-type__button" data-type-toggle
                        aria-haspopup="listbox" aria-expanded="false" aria-label="Answer type">
                    <svg class="fb-type__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                         data-type-icon>{!! \App\Support\FormFieldType::icon($type) !!}</svg>
                    <span data-type-label>{{ \App\Support\FormFieldType::label($type) }}</span>
                    <svg class="fb-type__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>

                <div class="fb-type__menu" data-type-menu role="listbox" hidden aria-label="Answer type">
                    @foreach (\App\Support\FormFieldType::grouped() as $group => $types)
                        <p class="fb-type__group" role="presentation">{{ $group }}</p>
                        @foreach ($types as $value => $spec)
                            <button type="button" class="fb-type__option" role="option"
                                    data-type-value="{{ $value }}"
                                    aria-selected="{{ $type === $value ? 'true' : 'false' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                     stroke-linecap="round" stroke-linejoin="round">{!! $spec['icon'] !!}</svg>
                                <span>{{ $spec['label'] }}</span>
                                <svg class="fb-type__tick" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>
                            </button>
                        @endforeach
                    @endforeach
                </div>
            </div>
            @error($errorKey . '.field_type') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        {{-- Required, beside the question it applies to. --}}
        <label class="fb-req" title="People cannot submit the form without answering this">
            <input type="hidden" name="fields[{{ $r }}][is_required]" value="0">
            <input type="checkbox" name="fields[{{ $r }}][is_required]" value="1" data-row-required
                   @checked(! empty($row['is_required']))>
            <span>Required</span>
        </label>

        {{-- Delete alone. Reordering is the grip on the left — the ▲/▼ buttons
             said the same thing twice, and duplicating a question is rarer than
             the icon for it was prominent. --}}
        <span class="fb-field__tools">
            <button type="button" class="fb-del" data-row-remove aria-label="Delete question" title="Delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
            </button>
        </span>
    </div>

    {{-- ------------------------ WHAT THE TYPE NEEDS ------------------------
         Shown only for the types that cannot work without it. Everything else a
         field could carry — key, placeholder, help text, default value, length
         limits — is left out of this screen on purpose: the question and its
         answer type are the decision, and the rest is noise while making it. --}}
    <div class="fb-field__extra" data-row-extra hidden>

        {{-- Options, for the choice types. Every option is the admin's: the
             module ships no default list for any question. --}}
        <div data-when="options" hidden>
            <div class="fb-sub__head">
                <h3 class="fb-sub__title">Options</h3>
                <button type="button" class="btn-soft" data-option-add>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add Option
                </button>
            </div>
            @error($errorKey . '.options') <p class="form-error">{{ $message }}</p> @enderror

            <div data-options>
                @foreach ($options as $o => $option)
                    @include('backend.forms.partials.option-row', ['r' => $r, 'o' => $o, 'option' => $option])
                @endforeach
            </div>
            <p class="form-hint" data-options-empty @if (count($options)) hidden @endif>
                No options yet — add the choices people will pick from.
            </p>

            {{-- The right answer, on a Multiple choice question of a Quiz. A list
                 of this question's own options rather than a text box, so it
                 cannot name one that does not exist; the builder script rebuilds
                 it as options are typed, added and removed. Hidden on a standard
                 form but still posted, so a quiz switched to standard and back
                 keeps its answers. --}}
            <div class="fb-correct" data-when="correct" hidden>
                <label class="fb-sub__title" for="correct_{{ $r }}">Correct answer</label>
                <select id="correct_{{ $r }}" name="fields[{{ $r }}][correct_answer]" class="form-control-hm"
                        data-correct data-selected="{{ $row['correct_answer'] ?? '' }}">
                    <option value="">— Choose the correct option —</option>
                </select>
                @error($errorKey . '.correct_answer') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- A grid's two lists: rows down the side, columns across the top. --}}
        <div data-when="grid" hidden>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="fb-sub__head">
                        <h3 class="fb-sub__title">Rows</h3>
                        <button type="button" class="btn-soft" data-grid-add="rows">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            Add Row
                        </button>
                    </div>
                    <div data-grid="rows">
                        @foreach ($row['rows'] ?? [] as $g => $gridRow)
                            @include('backend.forms.partials.option-row', ['r' => $r, 'o' => $g, 'option' => $gridRow, 'list' => 'rows'])
                        @endforeach
                    </div>
                    <p class="form-hint" data-grid-empty="rows" @if (count($row['rows'] ?? [])) hidden @endif>
                        No rows yet — these are the things being asked about.
                    </p>
                    @error($errorKey . '.rows') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <div class="fb-sub__head">
                        <h3 class="fb-sub__title">Columns</h3>
                        <button type="button" class="btn-soft" data-grid-add="columns">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            Add Column
                        </button>
                    </div>
                    <div data-grid="columns">
                        @foreach ($row['columns'] ?? [] as $g => $gridCol)
                            @include('backend.forms.partials.option-row', ['r' => $r, 'o' => $g, 'option' => $gridCol, 'list' => 'columns'])
                        @endforeach
                    </div>
                    <p class="form-hint" data-grid-empty="columns" @if (count($row['columns'] ?? [])) hidden @endif>
                        No columns yet — these are the answers to choose from.
                    </p>
                    @error($errorKey . '.columns') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="form-hint" data-when="tick_grid" hidden>
                A tick box grid lets people choose <strong>more than one</strong> column per row.
            </p>
        </div>

        {{-- A scale's ends. The range is the admin's — 1 to 5 is only where the
             boxes start. --}}
        <div class="row g-3" data-when="scale-block" hidden>
            <div class="col-6 col-md-3">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Scale from</label>
                    <input type="number" step="1"
                           name="fields[{{ $r }}][scale_min]" class="form-control-hm"
                           value="{{ $row['scale_min'] ?? '' }}"
                           placeholder="{{ \App\Support\FormFieldType::SCALE_DEFAULT_MIN }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Scale to</label>
                    <input type="number" step="1"
                           name="fields[{{ $r }}][scale_max]" class="form-control-hm"
                           value="{{ $row['scale_max'] ?? '' }}"
                           placeholder="{{ \App\Support\FormFieldType::SCALE_DEFAULT_MAX }}">
                    @error($errorKey . '.scale_max') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Label for the low end</label>
                    <input type="text" name="fields[{{ $r }}][scale_min_label]" class="form-control-hm"
                           value="{{ $row['scale_min_label'] ?? '' }}" placeholder="e.g. Not at all">
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Label for the high end</label>
                    <input type="text" name="fields[{{ $r }}][scale_max_label]" class="form-control-hm"
                           value="{{ $row['scale_max_label'] ?? '' }}" placeholder="e.g. Extremely">
                </div>
            </div>
        </div>

        {{-- A rating's shape and how many of them. --}}
        <div class="row g-3" data-when="rating-block" hidden>
            <div class="col-6 col-md-3">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">How many icons</label>
                    <input type="number" min="2" step="1"
                           name="fields[{{ $r }}][rating_count]" class="form-control-hm"
                           value="{{ $row['rating_count'] ?? '' }}"
                           placeholder="{{ \App\Support\FormFieldType::RATING_DEFAULT_COUNT }}">
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="form-row" style="margin-bottom:0">
                    <label class="form-label">Icon</label>
                    <select name="fields[{{ $r }}][rating_icon]" class="form-control-hm">
                        @foreach (\App\Support\FormFieldType::RATING_ICONS as $value => $label)
                            <option value="{{ $value }}" @selected(($row['rating_icon'] ?? 'star') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- A Hidden question is never shown to a visitor; this is the value it
             quietly records with every response (a source, a campaign, a batch).
             Without it the type recorded nothing at all. The input is posted for
             every type, so a default value set elsewhere is carried, not lost. --}}
        <div data-when="hidden-note" hidden>
            <div class="form-row" style="margin-bottom:0">
                <label class="form-label" for="default_{{ $r }}">Value to record</label>
                <input type="text" id="default_{{ $r }}" name="fields[{{ $r }}][default_value]" class="form-control-hm"
                       value="{{ $row['default_value'] ?? '' }}" placeholder="e.g. instagram-campaign">
                <p class="form-hint">Not shown on the form. Saved with every response, so you can tell where it came from.</p>
            </div>
        </div>

        {{-- A file field states its own limits on the public page from the safe
             defaults; this is only here to widen or narrow them. --}}
        <div data-when="file" hidden>
            <div class="form-row">
                <label class="form-label">Allowed file types</label>
                {{-- A fixed list, not a free-text box. The module refuses
                     anything outside it server-side too, so an executable
                     cannot be allowed by typing its extension in. --}}
                <div class="fb-checks">
                    @foreach (\App\Support\FormFieldType::ALLOWED_FILE_EXTENSIONS as $ext)
                        <label class="fb-check">
                            <input type="checkbox" name="fields[{{ $r }}][file_types][]" value="{{ $ext }}"
                                   @checked(in_array($ext, (array) ($row['file_types'] ?? []), true))>
                            <span>.{{ $ext }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="form-hint">
                    Tick nothing and the safe default is used
                    ({{ strtoupper(implode(', ', \App\Support\FormFieldType::DEFAULT_FILE_EXTENSIONS)) }}),
                    up to {{ \App\Support\UploadLimit::label() }}.
                </p>
            </div>

            <div class="form-row" style="margin-bottom:0">
                <label class="switch">
                    <input type="hidden" name="fields[{{ $r }}][multiple]" value="0">
                    <input type="checkbox" name="fields[{{ $r }}][multiple]" value="1"
                           @checked(! empty($row['multiple']))>
                    <span class="switch__track"></span>
                    <span class="switch__label">Allow several files</span>
                </label>
            </div>
        </div>
    </div>
</div>
