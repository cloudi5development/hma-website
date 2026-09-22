{{--
| The heading block at the top of a Placement Readiness section: the small
| gold-square label, the big line, and the paragraph under it — the same shape
| every section heading on this site uses.
|
|   @include('frontend.partials.placement-head', ['section' => $section])
|
| Styled by assets/css/frontend/placement-readiness.css, which the page pushes.
--}}
<div class="hm-pr-head">
    @if ($section->eyebrow)
        <span class="hm-pr-head__label">
            <span class="hm-pr-head__label-icon" aria-hidden="true"></span>
            <span class="hm-pr-head__label-text">{{ $section->eyebrow }}</span>
        </span>
    @endif

    <h2 class="hm-pr-head__title">{{ $section->title }}</h2>

    @if ($section->lead)
        <p class="hm-pr-head__desc">{{ $section->lead }}</p>
    @endif
</div>
