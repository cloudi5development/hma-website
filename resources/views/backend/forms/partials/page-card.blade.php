{{--
| One page (step) in the builder.
|
| $page  ['ref','id','title','description','sections']
|
| Like the section card, this is in the DOM for every form whatever its
| structure — a plain form is one page with its head hidden. Only one page is
| shown at a time on a multi-page form; the tabs above switch between them.
--}}
@php
    $ref = $page['ref'];
@endphp

<div class="fb-page" data-page data-page-ref="{{ $ref }}">

    <input type="hidden" name="pages[{{ $ref }}][id]" value="{{ $page['id'] ?? '' }}">

    {{-- The page's own heading. Hidden on a form with no pages, where this card
         is only a container. --}}
    <div class="fb-page__head" data-page-head data-needs="pages" hidden>
        <span class="fb-page__badge" data-page-number>1</span>

        <div class="fb-page__titles">
            <input type="text" name="pages[{{ $ref }}][title]" class="fb-page__title"
                   value="{{ $page['title'] ?? '' }}" data-page-title
                   placeholder="Page title, e.g. Personal Details" autocomplete="off">
            <input type="text" name="pages[{{ $ref }}][description]" class="fb-page__desc"
                   value="{{ $page['description'] ?? '' }}"
                   placeholder="Add a short description (optional)" autocomplete="off">
        </div>

        <button type="button" class="fb-ico fb-ico--danger" data-page-remove
                aria-label="Delete page" title="Delete page">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
        </button>
    </div>

    <div class="fb-page__body">
        {{-- Above the sections, not under them, and on the right — the same
             place and the same weight as Add Page, so the two controls that
             add structure to a form read as a pair. Only on a structure that
             has sections. --}}
        <div class="fb-secbar" data-needs="sections" hidden>
            <button type="button" class="fb-addbtn" data-section-add>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                Add Section
            </button>
        </div>

        <div data-sections>
            @foreach ($page['sections'] as $section)
                @include('backend.forms.partials.section-card', ['section' => $section, 'pageRef' => $ref])
            @endforeach
        </div>
    </div>
</div>
