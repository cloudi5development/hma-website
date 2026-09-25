@extends('frontend.layouts.template-base')

@section('title', 'Events — Hire Minds Academy')
@section('meta_description', 'Workshops, seminars and career guidance sessions at Hire Minds Academy. Browse every upcoming event and reserve your place.')

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- ?v=<file mtime> busts the browser cache whenever events.css changes, so
         edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/events.css') }}?v={{ filemtime(public_path('assets/css/frontend/events.css')) }}">
@endpush

@section('content')

    @php
        // $events is a LengthAwarePaginator of Event models (HomeController@events).
        $total = $events->total();
        $from  = $events->firstItem() ?? 0;
        $to    = $events->lastItem() ?? 0;
        $pages = range(1, $events->lastPage());
    @endphp

    <section class="hm-evl" aria-labelledby="hmEventsHeading">
        <div class="container">

            {{-- ============================== BANNER ============================== --}}
            <header class="hm-evl__banner">
                <img class="hm-evl__banner-img"
                     src="{{ asset('assets/images/events/event-listing.webp') }}"
                     alt="" role="presentation" width="1920" height="351" fetchpriority="high">

                <div class="hm-evl__banner-content">
                    <h1 class="hm-evl__banner-title" id="hmEventsHeading">Events</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-evl__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-evl__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Events</li>
                        </ol>
                    </nav>
                </div>
            </header>

            {{-- ============================= TOOLBAR ============================= --}}
            <div class="hm-evl__toolbar">
                {{-- A plain GET form, so searching works without JavaScript and the
                     result stays linkable. --}}
                <form class="hm-evl__search" method="GET" action="{{ route('frontend.events') }}" role="search">
                    <label class="visually-hidden" for="hmEventSearch">Search events</label>
                    <input class="hm-evl__search-input"
                           id="hmEventSearch"
                           type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder='Search "Design"'
                           autocomplete="off">
                    <button class="hm-evl__search-btn" type="submit" aria-label="Search events">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </form>

                <p class="hm-evl__count">
                    @if ($total)
                        Showing {{ $from }}&ndash;{{ $to }} of {{ $total }} Results
                    @else
                        No results
                    @endif
                </p>
            </div>

            {{-- =============================== GRID =============================== --}}
            <div class="row hm-evl__grid">
                @forelse ($events as $event)
                    {{-- col-6 on phones so two cards still share a row, as on the
                         blog listing; four across from xl. --}}
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <article class="hm-evl-card hm-evl-card--{{ $event->tone }}">

                            <img class="hm-evl-card__person"
                                 src="{{ $event->image_url }}"
                                 alt="{{ $event->speaker }}" loading="lazy" decoding="async">

                            <div class="hm-evl-card__body">
                                <div class="hm-evl-card__top">
                                    <span class="hm-evl-card__speaker">{{ $event->speaker }}</span>
                                    {{-- Paid / Free is read off the price rather than stored
                                         twice: an event with no price is a free one. The two
                                         carry different pill colours, as in the design. --}}
                                    @php $isPaid = filled($event->price); @endphp
                                    <span class="hm-evl-card__tag hm-evl-card__tag--{{ $isPaid ? 'paid' : 'free' }}">
                                        {{ $isPaid ? 'Paid' : 'Free' }}
                                    </span>
                                </div>

                                <h2 class="hm-evl-card__title">{{ $event->title }}</h2>

                                <ul class="hm-evl-card__meta">
                                    @if ($event->formatted_date)
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 3v3M16 3v3"/>
                                            </svg>
                                            <span>{{ $event->formatted_date }}</span>
                                        </li>
                                    @endif
                                    @if ($event->formatted_time)
                                        <li>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>
                                            </svg>
                                            <span>{{ $event->formatted_time }}</span>
                                        </li>
                                    @endif
                                </ul>

                                {{-- Inline SVG rather than fa-arrow-up-right: that icon
                                     is Font Awesome Pro, so on the free set it renders
                                     as nothing at all. --}}
                                <a class="hm-evl-card__btn" href="{{ route('frontend.event-details', $event->slug) }}">
                                    Event Details
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M7 17 17 7M8.5 7H17v8.5"/>
                                    </svg>
                                </a>
                            </div>

                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="hm-evl__empty">
                            @if ($search !== '')
                                No events match “{{ $search }}”.
                                <a href="{{ route('frontend.events') }}">Clear the search</a>
                            @else
                                No events scheduled just yet — check back soon.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- ============================ PAGINATION ============================ --}}
            @if ($events->lastPage() > 1)
                <nav class="hm-evl__pagination" aria-label="Event pages">
                    <a class="hm-evl__page hm-evl__page--prev" href="{{ $events->previousPageUrl() ?? '#' }}"
                       aria-label="Previous page" @if ($events->onFirstPage()) aria-disabled="true" @endif>
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </a>

                    @foreach ($pages as $page)
                        <a class="hm-evl__page @if ($page === $events->currentPage()) hm-evl__page--current @endif"
                           href="{{ $events->url($page) }}"
                           aria-label="Page {{ $page }}"
                           @if ($page === $events->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach

                    <a class="hm-evl__page hm-evl__page--next" href="{{ $events->nextPageUrl() ?? '#' }}"
                       aria-label="Next page" @if (! $events->hasMorePages()) aria-disabled="true" @endif>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </nav>
            @endif

        </div>
    </section>
@endsection
