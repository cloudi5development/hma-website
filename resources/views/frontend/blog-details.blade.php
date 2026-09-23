@extends('frontend.layouts.template-base')

{{-- Block sections, not the one-line @section('title', $value) form: that
     form stores e($value) and the layout escapes again when it yields, so an
     "&" in a post title would reach the browser as "&amp;". Same treatment as
     course-details. The meta below is the post's own - SEO -> Page SEO is keyed
     by route name and skips the details pages, since every post shares one. --}}
@php
    $blogMetaDescription = $blog->meta_description ?: $blog->excerpt;
@endphp

@section('title'){!! $blog->meta_title ?: $blog->title . ' — Hire Minds Academy' !!}@endsection

@if (filled($blogMetaDescription))
    @section('meta_description'){!! $blogMetaDescription !!}@endsection
@endif

@if (filled($blog->meta_keywords))
    @section('meta_keywords'){!! $blog->meta_keywords !!}@endsection
@endif

@push('styles')
    {{-- Poppins — the heading typeface used across the site's hero/banner blocks --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap">
    {{-- Same stylesheet as the listing: the banner and search row are shared
         components, so the detail page only adds the article + sidebar rules. --}}
    <link rel="stylesheet"
          href="{{ asset('assets/css/frontend/blog.css') }}?v={{ filemtime(public_path('assets/css/frontend/blog.css')) }}">
@endpush

@section('content')

    @php
        // $blog (the post) and $latestBlogs (the sidebar list) are Blog models
        // supplied by HomeController@blogDetails. Tags and socials stay static —
        // they are not part of the blog CMS.
        $tags = ['Education', 'HR Training', 'Online', 'Learn', 'Course', 'LMS'];

        // Icons ship as brand-coloured circles in assets/images/blog/.
        $socials = [
            ['name' => 'WhatsApp',  'icon' => 'whatsapp.png'],
            ['name' => 'Instagram', 'icon' => 'instagram.png'],
            ['name' => 'Facebook',  'icon' => 'facebook.png'],
            ['name' => 'YouTube',   'icon' => 'youtube.png'],
            ['name' => 'X',         'icon' => 'x.png'],
            ['name' => 'LinkedIn',  'icon' => 'linkedin.png'],
        ];
    @endphp

    <section class="hm-blog hm-blog--details">
        <div class="container">

            {{-- ============================== BANNER ============================== --}}
            <header class="hm-blog__banner">
                <img class="hm-blog__banner-img"
                     src="{{ asset('assets/images/blog/blog-header.webp') }}"
                     alt="" role="presentation">

                <div class="hm-blog__banner-content">
                    <h1 class="hm-blog__banner-title">Blog Detail</h1>

                    <nav aria-label="Breadcrumb">
                        <ol class="hm-blog__crumbs">
                            <li><a href="{{ route('frontend.index') }}">Home</a></li>
                            <li class="hm-blog__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li><a href="{{ route('frontend.blog') }}">Blogs</a></li>
                            <li class="hm-blog__crumb-sep" aria-hidden="true">&rsaquo;</li>
                            <li aria-current="page">Blog Details</li>
                        </ol>
                    </nav>
                </div>
            </header>

            {{-- ============================= TOOLBAR ============================= --}}
            <div class="hm-blog__toolbar">
                <div class="hm-blog__filters">

                    <div class="hm-blog__search">
                        <label class="visually-hidden" for="hmPostSearch">Search blog posts</label>
                        <input class="hm-blog__search-input"
                               id="hmPostSearch"
                               type="search"
                               name="q"
                               placeholder='Search "Design"'
                               autocomplete="off">
                        <img class="hm-blog__search-icon"
                             src="{{ asset('assets/images/blog/search.png') }}"
                             alt="" aria-hidden="true">
                    </div>

                    {{-- Vanilla dropdown — Bootstrap's JS bundle is not loaded
                         site-wide, so this stays dependency-free. --}}
                    <div class="hm-blog__cat" data-hm-cat>
                        <button class="hm-blog__cat-btn"
                                type="button"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="hmPostCatMenu">
                            <img class="hm-blog__cat-icon"
                                 src="{{ asset('assets/images/blog/menu.png') }}"
                                 alt="" aria-hidden="true">
                            <span class="hm-blog__cat-label">Categories</span>
                            <i class="fa-solid fa-chevron-down hm-blog__cat-caret" aria-hidden="true"></i>
                        </button>

                        <ul class="hm-blog__cat-menu" id="hmPostCatMenu">
                            @foreach (['All Categories', 'Design', 'Development', 'Career Advice', 'Interview Tips'] as $category)
                                <li><button type="button">{{ $category }}</button></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row hm-post__grid">

                {{-- ============================= ARTICLE ============================= --}}
                <div class="col-12 col-lg-9">
                    <article class="hm-post">

                        {{-- The figure sits flush at the top of the card; .hm-post__content
                             carries the padding for everything below it, so the image and the
                             copy read as one bordered panel. --}}
                        <figure class="hm-post__figure">
                            <img src="{{ $blog->image_url }}"
                                 alt="{{ $blog->image_alt_text }}"
                                 width="982" height="520">
                        </figure>

                        <div class="hm-post__content">

                            <div class="hm-post__meta">
                                <span class="hm-post__meta-item">
                                    <img class="hm-post__avatar"
                                         src="{{ asset('assets/images/blog/profile.png') }}"
                                         alt="" aria-hidden="true">
                                    {{ $blog->author }}
                                </span>

                                <span class="hm-post__meta-item">
                                    <img class="hm-post__meta-icon"
                                         src="{{ asset('assets/images/blog/calendar.png') }}"
                                         alt="" aria-hidden="true">
                                    <time datetime="{{ $blog->iso_date }}">{{ $blog->display_date }}</time>
                                </span>
                            </div>

                            <h2 class="hm-post__title">{{ $blog->title }}</h2>

                            {{-- Author-controlled HTML (headings, paragraphs, inline
                                 <a>/<strong>). Sanitise on input if untrusted authors
                                 ever get access. --}}
                            <div class="hm-post__body">
                                {!! $blog->content !!}
                            </div>

                        </div>

                    </article>
                </div>

                {{-- ============================= SIDEBAR ============================= --}}
                <div class="col-12 col-lg-3">
                    <aside class="hm-side">

                        @if ($latestBlogs->isNotEmpty())
                            <section class="hm-side__card" aria-labelledby="hmSideLatest">
                                <h2 class="hm-side__heading" id="hmSideLatest">The Latest</h2>

                                <ul class="hm-side__list">
                                    @foreach ($latestBlogs as $latest)
                                        <li>
                                            <a class="hm-side__item" href="{{ route('frontend.blog-details', $latest->slug) }}">
                                                <img src="{{ $latest->image_url }}"
                                                     alt="" aria-hidden="true" loading="lazy">
                                                <h3 class="hm-side__item-title">{{ $latest->title }}</h3>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>

                                <a class="hm-side__seeall" href="{{ route('frontend.blog') }}">See all</a>
                            </section>
                        @endif

                        <section class="hm-side__card" aria-labelledby="hmSideTags">
                            <h2 class="hm-side__heading" id="hmSideTags">Tags</h2>

                            <ul class="hm-side__tags">
                                @foreach ($tags as $tag)
                                    <li><a class="hm-tag" href="{{ route('frontend.blog') }}">{{ $tag }}</a></li>
                                @endforeach
                            </ul>
                        </section>

                        <section class="hm-side__card" aria-labelledby="hmSideConnect">
                            <h2 class="hm-side__heading" id="hmSideConnect">Connect</h2>

                            <ul class="hm-side__social">
                                @foreach ($socials as $social)
                                    <li>
                                        <a class="hm-social" href="#" aria-label="{{ $social['name'] }}">
                                            <img src="{{ asset('assets/images/blog/' . $social['icon']) }}"
                                                 alt="" aria-hidden="true" loading="lazy">
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>

                    </aside>
                </div>

            </div>
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

            var btn   = root.querySelector('.hm-blog__cat-btn');
            var label = root.querySelector('.hm-blog__cat-label');

            function close() {
                root.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = root.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            root.querySelectorAll('.hm-blog__cat-menu button').forEach(function (item) {
                item.addEventListener('click', function () {
                    label.textContent = item.textContent;
                    close();
                    btn.focus();
                });
            });

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
