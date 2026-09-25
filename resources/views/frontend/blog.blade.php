@extends('frontend.layouts.template-base')

@section('title', 'Blog — Hire Minds Academy')
@section('meta_description', 'Explore articles, career advice, interview tips, and industry updates from Hire Minds Academy — written to keep you ahead in a competitive job market.')

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- ?v=<file mtime> busts the browser cache whenever blog.css changes, so
         edits are never masked by a stale copy. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/blog.css') }}?v={{ filemtime(public_path('assets/css/frontend/blog.css')) }}">
@endpush

@section('content')

    @php
        // $blogs is a LengthAwarePaginator of Blog models (HomeController@blog).
        // 'tint' picks the card's gradient wash and still cycles by position, so
        // the grid looks identical — only the data source moved to the database.
        $tints       = ['blue', 'cream', 'pink', 'green', 'lavender', 'peach'];
        $total       = $blogs->total();
        $from        = $blogs->firstItem() ?? 0;
        $to          = $blogs->lastItem() ?? 0;
        $currentPage = $blogs->currentPage();
        $pages       = range(1, $blogs->lastPage());
    @endphp

    <section class="hm-blog" aria-labelledby="hmBlogHeading">
        <div class="container">

            {{-- ============================== BANNER ============================== --}}
            <header class="hm-blog__banner">
                <img class="hm-blog__banner-img"
                     src="{{ asset('assets/images/blog/blog-header.webp') }}"
                     alt="" role="presentation">

                <div class="hm-blog__banner-content">
                    <h1 class="hm-blog__banner-title" id="hmBlogHeading">Latest Blogs</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-blog__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-blog__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Blog</li>
                        </ol>
                    </nav>
                </div>
            </header>

            {{-- ============================= TOOLBAR ============================= --}}
            <div class="hm-blog__toolbar">
                {{-- A plain GET form: the chosen category and the search term
                     both travel in the query string, so a filtered listing can
                     be linked to, reloaded and paged through. --}}
                <form class="hm-blog__filters" method="GET" action="{{ route('frontend.blog') }}" role="search">

                    {{-- Keeps the chosen category when a search is submitted;
                         the category links below carry the search the same way. --}}
                    @if ($category !== '')
                        <input type="hidden" name="category" value="{{ $category }}">
                    @endif

                    <div class="hm-blog__search">
                        <label class="visually-hidden" for="hmBlogSearch">Search blog posts</label>
                        <input class="hm-blog__search-input"
                               id="hmBlogSearch"
                               type="search"
                               name="q"
                               value="{{ $search }}"
                               placeholder='Search "Design"'
                               autocomplete="off">
                        {{-- A real submit button, so the magnifier can be
                             tapped as well as Enter pressed. --}}
                        <button class="hm-blog__search-icon" type="submit" aria-label="Search blog posts">
                            <img src="{{ asset('assets/images/blog/search.png') }}" alt="" aria-hidden="true">
                        </button>
                    </div>

                    {{-- Vanilla dropdown — Bootstrap's JS bundle is not loaded
                         site-wide, so this stays dependency-free. --}}
                    <div class="hm-blog__cat" data-hm-cat>
                        <button class="hm-blog__cat-btn"
                                type="button"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="hmBlogCatMenu">
                            <img class="hm-blog__cat-icon"
                                 src="{{ asset('assets/images/blog/menu.png') }}"
                                 alt="" aria-hidden="true">
                            <span class="hm-blog__cat-label">{{ $category !== '' ? $category : 'Categories' }}</span>
                            <i class="fa-solid fa-chevron-down hm-blog__cat-caret" aria-hidden="true"></i>
                        </button>

                        {{-- The categories the posts themselves use (Blog::categories()),
                             not a list written here: a category typed on a post in the
                             panel appears straight away, and one nothing uses never does. --}}
                        <ul class="hm-blog__cat-menu" id="hmBlogCatMenu">
                            <li>
                                <a href="{{ route('frontend.blog', array_filter(['q' => $search])) }}"
                                   @class(['is-active' => $category === ''])>All Categories</a>
                            </li>
                            @foreach ($categories as $name)
                                <li>
                                    <a href="{{ route('frontend.blog', array_filter(['q' => $search, 'category' => $name])) }}"
                                       @class(['is-active' => $category === $name])
                                       @if ($category === $name) aria-current="true" @endif>{{ $name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </form>

                <p class="hm-blog__count">Showing {{ $from }}&ndash;{{ $to }} of {{ $total }} Results</p>
            </div>

            {{-- =============================== GRID =============================== --}}
            <div class="row hm-blog__grid">
                @forelse ($blogs as $blog)
                    @php $blogUrl = route('frontend.blog-details', $blog->slug); @endphp
                    {{-- col-6 from the smallest screen up: two cards per row on
                         phones too (blog.css tightens the card to suit). --}}
                    <div class="col-6">
                        <article class="hm-blog-card hm-blog-card--{{ $tints[$loop->index % count($tints)] }}">

                            <div class="hm-blog-card__media">
                                <img class="hm-blog-card__img"
                                     src="{{ $blog->image_url }}"
                                     alt="{{ $blog->image_alt_text }}"
                                     width="197" height="165" loading="lazy">
                            </div>

                            <div class="hm-blog-card__body">
                                <h2 class="hm-blog-card__title">
                                    {{-- The ::after on this link stretches over the whole
                                         card, so the card reads as one hit area while the
                                         keyboard still gets a single, real tab stop. --}}
                                    <a class="hm-blog-card__link" href="{{ $blogUrl }}">{{ $blog->title }}</a>
                                </h2>

                                <p class="hm-blog-card__desc">{{ $blog->excerpt }}</p>

                                <div class="hm-blog-card__foot">
                                    <span class="hm-blog-card__date">
                                        <img class="hm-blog-card__date-icon"
                                             src="{{ asset('assets/images/blog/calendar.png') }}"
                                             alt="" aria-hidden="true" loading="lazy" decoding="async">
                                        <time datetime="{{ $blog->iso_date }}">{{ $blog->display_date }}</time>
                                    </span>

                                    <a class="hm-blog-card__more" href="{{ $blogUrl }}">Read More</a>
                                </div>
                            </div>

                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="hm-blog__count">
                            @if ($category !== '' || $search !== '')
                                {{-- "No blog posts yet" would be a lie here: there are
                                     posts, just none matching this filter. --}}
                                Nothing matched that filter.
                                <a href="{{ route('frontend.blog') }}">Show all posts</a>
                            @else
                                No blog posts yet.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- ============================ PAGINATION ============================ --}}
            @if ($blogs->lastPage() > 1)
                <nav class="hm-blog__pagination" aria-label="Blog pages">
                    <a class="hm-blog__page hm-blog__page--prev" href="{{ $blogs->previousPageUrl() ?? '#' }}"
                       aria-label="Previous page" @if ($blogs->onFirstPage()) aria-disabled="true" @endif>
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    </a>

                    @foreach ($pages as $page)
                        <a class="hm-blog__page @if ($page === $currentPage) hm-blog__page--current @endif"
                           href="{{ $blogs->url($page) }}"
                           aria-label="Page {{ $page }}"
                           @if ($page === $currentPage) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach

                    <a class="hm-blog__page hm-blog__page--next" href="{{ $blogs->nextPageUrl() ?? '#' }}"
                       aria-label="Next page" @if (! $blogs->hasMorePages()) aria-disabled="true" @endif>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </nav>
            @endif

        </div>
    </section>
@endsection

@push('scripts')
    {{-- Category dropdown — open/close, click-outside and Escape. Enter/Space
         come free because the trigger is a real <button>. --}}
    <script>
        (function () {
            'use strict';

            var root = document.querySelector('[data-hm-cat]');
            if (!root) return;

            var btn = root.querySelector('.hm-blog__cat-btn');

            function close() {
                root.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = root.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            // Picking a category is a link now: the page reloads filtered and
            // the server puts the chosen name on the trigger, so there is
            // nothing to reflect here.

            document.addEventListener('click', function (e) {
                if (!root.contains(e.target)) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && root.classList.contains('is-open')) {
                    close();
                    btn.focus();
                }
            });
        })();
    </script>
@endpush
