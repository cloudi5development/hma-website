{{--
| One section in the builder — a heading with questions under it.
|
| $section  ['ref','id','title','description','fields']
| $pageRef  the page this section sits on. Posted as page_ref, and restamped by
|           the builder script whenever a section is dragged to another page.
|
| The whole card is present in the DOM on every form, whatever its structure —
| a plain form is one section with its head hidden. See FormBuilderTree for why
| that is what makes switching structures lose nothing.
--}}
@php
    $ref    = $section['ref'];
    $fields = $section['fields'] ?? [];
@endphp

<div class="fb-sec" data-section data-section-ref="{{ $ref }}" draggable="false">

    {{-- Present on a saved section, blank on one just added. Same job as a
         field's id: it is what lets a section be renamed instead of replaced,
         so the questions pointing at it stay where they are. --}}
    <input type="hidden" name="sections[{{ $ref }}][id]" value="{{ $section['id'] ?? '' }}">
    <input type="hidden" name="sections[{{ $ref }}][page_ref]" value="{{ $pageRef }}" data-section-page>

    {{-- One head, two faces. On a form with sections it is the section's own
         heading, with everything needed to manage it. On a form without, the
         card is only a container and the head is the plain "Questions" bar the
         builder has always had — which is what keeps a plain form looking
         almost exactly like the screen before any of this existed. --}}
    <div class="fb-sec__head">
        <span class="fb-sec__grip" data-section-grip data-needs="sections" hidden
              title="Drag to reorder" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/></svg>
        </span>

        <div class="fb-sec__titles" data-needs="sections" hidden>
            <input type="text" name="sections[{{ $ref }}][title]" class="fb-sec__title"
                   value="{{ $section['title'] ?? '' }}" data-section-title
                   placeholder="Section title, e.g. Personal Information" autocomplete="off">
            <input type="text" name="sections[{{ $ref }}][description]" class="fb-sec__desc"
                   value="{{ $section['description'] ?? '' }}"
                   placeholder="Add a short description (optional)" autocomplete="off">
        </div>

        <h2 class="fb-sec__plain hm-card__title" data-needs="flat">Questions</h2>

        <span class="fb-sec__count" data-section-count data-needs="sections" hidden></span>

        <button type="button" class="fb-ico" data-section-toggle data-needs="sections" hidden
                aria-label="Collapse section" title="Collapse">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <button type="button" class="fb-ico fb-ico--danger" data-section-remove data-needs="sections" hidden
                aria-label="Delete section" title="Delete section">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
        </button>

        {{-- The + that adds a question, at the right of the heading because that
             is where the eye goes looking for it. On every structure. --}}
        <button type="button" class="fb-plus" data-field-add
                aria-label="Add a question" title="Add a question">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        </button>
    </div>

    <div class="fb-sec__body" data-section-body>
        <div data-fields>
            @foreach ($fields as $r => $row)
                @include('backend.forms.partials.field-row', [
                    'r' => $r, 'row' => $row, 'pageRef' => $pageRef, 'sectionRef' => $ref,
                ])
            @endforeach
        </div>

        {{-- Two ways to add questions, side by side. Bulk Upload fills THIS
             section — pages and sections are made by hand, and a sheet only
             ever adds questions — so it lives with the section rather than once
             for the whole form. --}}
        <div class="fb-addrow">
            <button type="button" class="btn-soft" data-field-add>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add Question
            </button>
            <button type="button" class="btn-ghost" data-bulk-open>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m0 0-4 4m4-4 4 4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg>
                Bulk Upload
            </button>
        </div>
    </div>
</div>
