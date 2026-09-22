@extends('frontend.layouts.template-base')

{{--
| /placement-readiness — the Placement Success Program page.
|
| Nothing on this page is written here: every heading, paragraph, card, band,
| module, step, format and button comes from Admin → Placement Readiness
| (PlacementSection + PlacementItem). A block switched off, or one whose rows
| are all hidden, simply is not drawn — so the markup below never assumes a
| block exists or that a list has anything in it.
--}}

@section('title', 'Placement Readiness — Hire Minds Academy')
@section('meta_description', 'Recruiter-led placement preparation for colleges: a readiness diagnostic, targeted training, full mock recruitment and placement support with measurable outcomes.')

@push('styles')
    {{-- Poppins — the heading typeface the site's banner blocks use --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/placement-readiness.css') }}?v={{ filemtime(public_path('assets/css/frontend/placement-readiness.css')) }}">
@endpush

@php
    /** One block, or null when it is switched off / not seeded. */
    $block = fn (string $key) => $sections[$key] ?? null;

    /** A block's rows in one of its lists — an empty collection when there are none. */
    $rows = fn (?App\Models\PlacementSection $section, string $group) => $section ? $section->visibleIn($group) : collect();

    $hero = $block('hero');
@endphp

@section('content')

<div class="hm-pr-page">

    {{-- ================================ HERO ================================ --}}
    @if ($hero)
        <section class="hm-pr-hero" id="placement-hero">
            <span class="hm-pr-hero__glow" aria-hidden="true"></span>
            <div class="container">
                <nav aria-label="Breadcrumb">
                    <ol class="hm-pr-crumbs">
                        <li><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li class="hm-pr-crumbs__sep" aria-hidden="true">&rsaquo;</li>
                        <li aria-current="page">Placement Readiness</li>
                    </ol>
                </nav>

                <div class="hm-pr-hero__grid">
                    <div class="hm-pr-hero__main">
                        @if ($hero->eyebrow)
                            <span class="hm-pr-hero__eyebrow">{{ $hero->eyebrow }}</span>
                        @endif

                        <h1 class="hm-pr-hero__title">{{ $hero->title }}</h1>

                        @if ($hero->lead)
                            <p class="hm-pr-hero__lead">{{ $hero->lead }}</p>
                        @endif

                        @if ($hero->primary_label || $hero->secondary_label)
                            <div class="hm-pr-hero__actions">
                                @if ($hero->primary_label)
                                    <a class="hm-pr-btn hm-pr-btn--primary" href="{{ $hero->primary_url ?: '#placement-cta' }}">
                                        <span>{{ $hero->primary_label }}</span>
                                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if ($hero->secondary_label)
                                    <a class="hm-pr-btn hm-pr-btn--ghost" href="{{ $hero->secondary_url ?: route('frontend.contact-us') }}">
                                        <span>{{ $hero->secondary_label }}</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($hero->note || $rows($hero, 'point')->isNotEmpty())
                        <aside class="hm-pr-hero__card">
                            @if ($hero->note)
                                <h2 class="hm-pr-hero__card-title">{{ $hero->note }}</h2>
                            @endif
                            @if ($rows($hero, 'point')->isNotEmpty())
                                <ul class="hm-pr-hero__list">
                                    @foreach ($rows($hero, 'point') as $point)
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                            {{ $point->title }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </aside>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ============================== CHALLENGE ============================== --}}
    @php $challenge = $block('challenge'); @endphp
    @if ($challenge)
        <section class="hm-pr-sec" id="placement-challenge">
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $challenge])

                @if ($rows($challenge, 'card')->isNotEmpty())
                    <div class="row g-4">
                        @foreach ($rows($challenge, 'card') as $i => $card)
                            <div class="col-12 col-md-6 col-lg-4">
                                <article class="hm-pr-card hm-pr-card--numbered">
                                    <span class="hm-pr-card__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3 class="hm-pr-card__title">{{ $card->title }}</h3>
                                    @if ($card->text) <p class="hm-pr-card__text">{{ $card->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================== FRAMEWORK ============================== --}}
    @php $framework = $block('framework'); @endphp
    @if ($framework)
        <section class="hm-pr-sec hm-pr-sec--dark" id="placement-framework">
            <span class="hm-pr-sec__glow" aria-hidden="true"></span>
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $framework])

                @if ($rows($framework, 'stage')->isNotEmpty())
                    <div class="row g-4">
                        @foreach ($rows($framework, 'stage') as $i => $stage)
                            <div class="col-12 col-md-6 col-lg-3">
                                <article class="hm-pr-stage">
                                    <span class="hm-pr-stage__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <h3 class="hm-pr-stage__title">{{ $stage->title }}</h3>
                                    @if ($stage->text) <p class="hm-pr-stage__text">{{ $stage->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================== DIAGNOSTIC ============================= --}}
    @php $diagnostic = $block('diagnostic'); @endphp
    @if ($diagnostic)
        <section class="hm-pr-sec hm-pr-sec--tint" id="placement-diagnostic">
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $diagnostic])

                @if ($rows($diagnostic, 'band')->isNotEmpty())
                    <div class="hm-pr-table__wrap">
                        <table class="hm-pr-table">
                            <thead>
                                <tr>
                                    <th scope="col">Classification</th>
                                    <th scope="col">Score</th>
                                    <th scope="col">Recommended intervention</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows($diagnostic, 'band') as $band)
                                    <tr>
                                        <td data-label="Classification"><strong>{{ $band->title }}</strong></td>
                                        <td data-label="Score"><span class="hm-pr-score">{{ $band->subtitle }}</span></td>
                                        <td data-label="Recommended intervention">{{ $band->text }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($rows($diagnostic, 'audience')->isNotEmpty())
                    <div class="row g-4 mt-1">
                        @foreach ($rows($diagnostic, 'audience') as $card)
                            <div class="col-12 col-md-6 col-lg-4">
                                <article class="hm-pr-card">
                                    <h3 class="hm-pr-card__title">{{ $card->title }}</h3>
                                    @if ($card->text) <p class="hm-pr-card__text">{{ $card->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- =============================== MODULES ============================== --}}
    @php $modules = $block('modules'); @endphp
    @if ($modules)
        <section class="hm-pr-sec" id="placement-modules">
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $modules])

                @if ($rows($modules, 'module')->isNotEmpty())
                    <div class="row g-4">
                        @foreach ($rows($modules, 'module') as $i => $module)
                            <div class="col-12 col-md-6">
                                <article class="hm-pr-card hm-pr-card--module" style="--accent: {{ $i % 2 ? '#E9A320' : '#843D21' }}">
                                    <h3 class="hm-pr-card__title">{{ $module->title }}</h3>
                                    @if ($module->text) <p class="hm-pr-card__text">{{ $module->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================ MOCK ================================ --}}
    @php $mock = $block('mock'); @endphp
    @if ($mock)
        <section class="hm-pr-sec hm-pr-sec--tint" id="placement-mock">
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $mock])

                @if ($rows($mock, 'step')->isNotEmpty())
                    <ol class="hm-pr-flow">
                        @foreach ($rows($mock, 'step') as $i => $step)
                            <li class="hm-pr-flow__step">
                                <span class="hm-pr-flow__num">{{ $i + 1 }}</span>
                                <span class="hm-pr-flow__label">{{ $step->title }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if ($rows($mock, 'outcome')->isNotEmpty())
                    <div class="row g-4">
                        @foreach ($rows($mock, 'outcome') as $card)
                            <div class="col-12 col-md-6">
                                <article class="hm-pr-card">
                                    <h3 class="hm-pr-card__title">{{ $card->title }}</h3>
                                    @if ($card->text) <p class="hm-pr-card__text">{{ $card->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($mock->note)
                    {{-- Anything before the first colon is the lead-in ("A responsible
                         promise:"), printed in bold. Admin-editable either way. --}}
                    <p class="hm-pr-promise">
                        @if (str_contains($mock->note, ':'))
                            <strong>{{ Str::before($mock->note, ':') }}:</strong>{{ Str::after($mock->note, ':') }}
                        @else
                            {{ $mock->note }}
                        @endif
                    </p>
                @endif
            </div>
        </section>
    @endif

    {{-- =============================== FORMATS ============================== --}}
    @php $formats = $block('formats'); @endphp
    @if ($formats)
        <section class="hm-pr-sec" id="placement-formats">
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $formats])

                @if ($rows($formats, 'format')->isNotEmpty())
                    <div class="hm-pr-table__wrap">
                        <table class="hm-pr-table">
                            <thead>
                                <tr>
                                    <th scope="col">Format</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col">Best suited for</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows($formats, 'format') as $format)
                                    <tr>
                                        <td data-label="Format"><strong>{{ $format->title }}</strong></td>
                                        <td data-label="Duration"><span class="hm-pr-score">{{ $format->subtitle }}</span></td>
                                        <td data-label="Best suited for">{{ $format->text }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================= WHY ================================ --}}
    @php $why = $block('why'); @endphp
    @if ($why)
        <section class="hm-pr-sec hm-pr-sec--dark" id="placement-why">
            <span class="hm-pr-sec__glow" aria-hidden="true"></span>
            <div class="container">
                @include('frontend.partials.placement-head', ['section' => $why])

                @if ($rows($why, 'feature')->isNotEmpty())
                    <div class="row g-4">
                        @foreach ($rows($why, 'feature') as $feature)
                            <div class="col-12 col-md-6 col-lg-4">
                                <article class="hm-pr-stage">
                                    <h3 class="hm-pr-stage__title">{{ $feature->title }}</h3>
                                    @if ($feature->text) <p class="hm-pr-stage__text">{{ $feature->text }}</p> @endif
                                </article>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================= CTA ================================ --}}
    @php $cta = $block('cta'); @endphp
    @if ($cta)
        <section class="hm-pr-cta" id="placement-cta">
            <span class="hm-pr-cta__glow" aria-hidden="true"></span>
            <div class="container">
                <div class="hm-pr-cta__grid">
                    <div>
                        @if ($cta->eyebrow)
                            <span class="hm-pr-hero__eyebrow">{{ $cta->eyebrow }}</span>
                        @endif
                        <h2 class="hm-pr-cta__title">{{ $cta->title }}</h2>
                        @if ($cta->lead) <p class="hm-pr-cta__lead">{{ $cta->lead }}</p> @endif
                    </div>

                    <div class="hm-pr-cta__actions">
                        @if ($cta->primary_label)
                            <a class="hm-pr-btn hm-pr-btn--light" href="{{ $cta->primary_url ?: route('frontend.contact-us') }}">
                                <span>{{ $cta->primary_label }}</span>
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($cta->phone)
                            <a class="hm-pr-cta__link" href="tel:{{ preg_replace('/[^0-9+]/', '', $cta->phone) }}">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $cta->phone }}
                            </a>
                        @endif
                        @if ($cta->email)
                            <a class="hm-pr-cta__link" href="mailto:{{ $cta->email }}">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ $cta->email }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

</div>

@endsection
