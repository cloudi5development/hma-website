{{--
|--------------------------------------------------------------------------
| The shape of a form, on the page people fill in
|--------------------------------------------------------------------------
|
| Draws whatever structure the admin chose — one flat list, groups under
| headings, a sequence of steps, or steps that are themselves grouped — from
| one loop over App\Support\FormLayout, which hands back the same
| pages → sections → questions nesting for all four.
|
| The controls themselves are still form-fields.blade.php's job. This partial
| only decides what sits around them, which is why a question renders and
| validates identically whichever shape of form it happens to be on.
|
| Used by the public page and the admin preview, so what an admin previews is
| the same HTML a visitor gets.
|
|   @include('frontend.partials.form-structure', ['form' => $form])
|
| Multi-page is PROGRESSIVE: every step is in the page, and the script at the
| foot of the public page hides all but one. With no JavaScript the form is a
| single long page that still submits correctly — one request carries the whole
| form either way, so nothing about a step is a checkpoint the server knows or
| cares about.
--}}
@php
    $layout = $form->layout();

    // One page is not a sequence. A multi-page form that has only ever been
    // given one page is drawn as the plain form it currently is, rather than
    // with a "Step 1 of 1" and a Next button that has nowhere to go.
    $paged = $form->hasPages() && count($layout) > 1;
@endphp

@if ($paged)
    {{-- Where the visitor is, in two forms: named, and counted. The named list
         is the useful one — "Documents" tells you what is still coming — and
         the count is the reassuring one. --}}
    <div class="hmf-steps__head" data-step-head>
        <ol class="hmf-steps__crumbs">
            @foreach ($layout as $i => $page)
                <li class="hmf-steps__crumb" data-step-crumb="{{ $i }}">
                    <span class="hmf-steps__dot" aria-hidden="true">{{ $i + 1 }}</span>
                    <span class="hmf-steps__name">{{ $page['heading'] }}</span>
                </li>
            @endforeach
        </ol>

        {{-- One segment per step rather than one sliding bar: the segments say
             how many steps there are as well as how far along you are, which a
             continuous bar cannot. Decorative — the count beside it is what is
             announced. --}}
        <div class="hmf-steps__meter">
            <div class="hmf-steps__segs" role="presentation" aria-hidden="true">
                @foreach ($layout as $i => $page)
                    <span class="hmf-steps__seg" data-step-seg="{{ $i }}"></span>
                @endforeach
            </div>
            <p class="hmf-steps__count" aria-live="polite">
                Step <span data-step-now>1</span> of {{ count($layout) }}
            </p>
        </div>
    </div>
@endif

<div class="hmf-steps @if ($paged) is-paged @endif" data-steps="{{ count($layout) }}">
    @foreach ($layout as $i => $page)
        {{-- Only the first step is visible to begin with, and only when the
             script that moves between them is going to run — see the noscript
             block below, which puts them all back. --}}
        <section class="hmf-step" data-step="{{ $i }}"
                 @if ($paged) aria-label="{{ $page['heading'] }}" @endif
                 @if ($paged && $i > 0) hidden @endif>

            {{-- The page's NAME is not repeated here. It is already on the step
                 above, which is where someone looks to see where they are —
                 printing it again as a heading directly underneath said the
                 same word twice and made the form look like it had two titles.
                 The step still carries it as its accessible name, so a screen
                 reader announces the step without the page showing it twice.

                 The description is not a repeat of anything, so it stays. --}}
            @if ($paged && filled($page['description']))
                <header class="hmf-step__head">
                    <p class="hmf-step__sub">{{ $page['description'] }}</p>
                </header>
            @endif

            @foreach ($page['sections'] as $section)
                @php
                    /* A section with neither a title nor a description is not a
                       section the admin made. It is the plain form's single
                       implicit group, and the bucket FormLayout puts a question
                       in when its own section has been deleted — so it gets no
                       heading it was never given, and no card either: a lone box
                       drawn around every question on a plain form would be a box
                       inside a box saying nothing. */
                    $named = filled($section['title']) || filled($section['description']);
                @endphp

                <div class="hmf-sec @if ($named) hmf-sec--card @endif">
                    @if ($named)
                        <header class="hmf-sec__head">
                            @if (filled($section['title']))
                                <h3 class="hmf-sec__title">{{ $section['title'] }}</h3>
                            @endif
                            @if (filled($section['description']))
                                <p class="hmf-sec__sub">{{ $section['description'] }}</p>
                            @endif
                        </header>
                    @endif

                    {{-- The grid that lays questions out two to a row. It sits
                         here rather than on the form, so each group is its own
                         grid and a section never starts half-way along a row
                         left over from the one before it. --}}
                    <div class="hmf-qs">
                        @include('frontend.partials.form-fields', ['fields' => $section['fields']])
                    </div>
                </div>
            @endforeach
        </section>
    @endforeach
</div>

@if ($paged)
    {{-- Back and Next. Written here rather than built by the script so that the
         buttons are part of the document, and so the noscript rule below can
         take them away rather than having to put them back. --}}
    <div class="hmf-steps__nav" data-step-nav hidden>
        <button type="button" class="hmf-step__btn hmf-step__btn--back" data-step-back>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Back</span>
        </button>
        <button type="button" class="hmf-step__btn hmf-step__btn--next" data-step-next>
            <span>Next</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Without JavaScript there are no steps to move between, so every one of
         them is shown and the ordinary submit button at the foot of the form is
         the only control needed. The form posts the whole thing in one request
         regardless, so this is a complete form and not a degraded one. --}}
    <noscript>
        <style>
            .hmf-step[hidden] { display: block !important; }
            .hmf-steps__head, .hmf-steps__nav { display: none !important; }
        </style>
    </noscript>
@endif
