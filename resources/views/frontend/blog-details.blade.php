@extends('frontend.layouts.template-base')

@section('title', 'How to Prepare for Your First Technical Interview — Hire Minds Academy')
@section('meta_description', 'A first-hand guide to preparing for your first technical interview — what to study, how to practise, and the habits that separate strong candidates from the rest.')

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
        // ------------------------------------------------------------------
        // Post. Swap for the Blog model once it lands — the markup below only
        // reads these keys. 'content' is an ordered list of blocks so the
        // article renders from a loop rather than hardcoded markup.
        //
        // Block shapes:
        //   ['type' => 'heading',     'text'  => '…']
        //   ['type' => 'p',      'text'  => '…']   inline <a>/<strong> allowed
        //   ['type' => 'figure', 'image' => '…', 'alt' => '…']
        // ------------------------------------------------------------------
        $blog = [
            'title'    => 'How to Prepare for Your First Technical Interview',
            'image'    => 'blog-11.webp',
            'author'   => 'Hireminds Academy Admin',
            'avatar'   => 'profile.png',
            'date'     => '20 July, 2024',
            'datetime' => '2024-07-20',
            'content'  => [
                ['type' => 'p', 'text' => 'Hello there! As a marketing manager in the SaaS industry, you might be looking for innovative ways to engage your audience. I bet generative AI has crossed your mind as an option for creating content. Well, let me share from my firsthand experience.'],
                ['type' => 'p', 'text' => 'Google encourages high-quality blogs regardless of whether they\'re <a href="#">written by humans or created using artificial intelligence</a> like ChatGPT. Here\'s what matters: producing original material with expertise and trustworthiness based on Google <a href="#">E-E-A-T principles</a>.'],
                ['type' => 'p', 'text' => 'This means focusing more on producing content for writing rather than primarily employing AI tools to manipulate search rankings. There comes a time when many experienced professionals want to communicate their insights but get stuck due to limited writing skills — that\'s where <strong>Generative AI</strong> can step in.'],
                ['type' => 'p', 'text' => 'So, together, we\'re going explore how this technology could help us deliver valuable content without sounding robotic or defaulting into mere regurgitations of existing materials (spoiler alert — common pitfalls). Hang tight — it\'ll be a fun learning journey!'],

                ['type' => 'heading', 'text' => 'Steering Clear of Common AI Writing Pitfalls'],
                ['type' => 'p', 'text' => 'Jumping headfirst into using AI, like <a href="#">ChatGPT</a>, without a content strategy can lead to some unfortunate results. One common pitfall I\'ve seen is people opting for <strong>quantity over quality</strong> — they churn out blogs, but each one feels robotic and soulless, reading just like countless others on the internet.'],
                ['type' => 'p', 'text' => 'Another fault line lies in <strong>creating reproductions</strong> rather than delivering unique perspectives that offer value to readers; it often happens if you let an AI tool write your full blog unrestrained! Trust me on this — Ask any experienced marketer or writer about their takeaways from using generative AI tools. They\'ll all agree that adding a human touch and following specific guidelines are key when implementing these tech pieces.'],
                ['type' => 'p', 'text' => 'Remember, our goal here isn\'t merely satisfying search engines but, more importantly, <strong>knowledge-hungry humans seeking reliable information online</strong>. So keep your audience\'s needs at heart while leveraging technology\'s assistance!'],

                ['type' => 'heading', 'text' => 'Understanding ChatGPT Capabilities — Define Your Style'],
                ['type' => 'p', 'text' => 'Welcome to the intriguing world of ChatGPT! Its ability and potential can truly be mind-boggling. I have learned from experience how capable it is in dealing with diverse content generation tasks, only that its text sounded slightly "unnatural" <a href="#">in accordance with TechTarget</a>. However, fear not — there are ways around this!'],
                ['type' => 'p', 'text' => 'One strategic move I\'ve seen work wonders is <strong>defining your unique writing style</strong> first before handing over the reins to AI; you treat it like a canvas whereupon our vision opens up. If we deeply instruct who we\'re targeting or what tone resonates more effectively, generative AI tools such as ChatGPT will comply remarkably well.'],
                ['type' => 'p', 'text' => 'In framing guidelines, remember to keep audience interests at heart while adopting technology\'s benefits for efficient output — trust me on this because neglecting these aspects could backfire by generating unappealing robotic-like essays.'],
                ['type' => 'p', 'text' => 'Ultimately, aiming towards reader-focused-driven creativity illuminated under authentically human/bot narratives holds priority above all else when crafting blogs using auto-generation toolkits!'],

                ['type' => 'heading', 'text' => 'Understand Your Readers'],
                ['type' => 'p', 'text' => 'Understanding your readers is vital when producing blog posts. It\'s not about filling blanks with popular search terms, no matter how much keyword research you do. Real readability goes beyond that! Your content has to "speak" directly to your target audience.'],
                ['type' => 'p', 'text' => 'Building an <strong>Ideal Customer Profile (ICP)</strong> can help immensely in this respect <a href="#">(Dan Martell)</a>. This tool identifies specific demographics or psychographic-driven behind customer success — a valuable guide for creating targeted outputs catering to arrayed reader types.'],
                ['type' => 'p', 'text' => 'Simultaneously, SEO aspects also need attention: identifying suitable keywords &amp; phrases people commonly use enhances reach (SEO.COM reference). Yet remember — human appeal doesn\'t mean packing text up firmly with rich SEO-friendly but keeping little value substance and stuffing it full with only "keywords."'],

                ['type' => 'heading', 'text' => 'Creating Quality AI-powered Blogs that Stand Out'],
                ['type' => 'p', 'text' => 'Creating brilliant AI-powered blogs is a fun blending of logic with just the right dose of creativity. From defining your target audience to tuning in ChatGPT\'s language style, every step counts towards producing content that not only ranks well but is also enjoyable and valuable to readers.'],
                ['type' => 'p', 'text' => 'One tactic I\'ve found useful is maintaining originality in message resonance, with <strong>unique perspectives</strong> infusing life beyond words onto pages!'],
                ['type' => 'p', 'text' => 'Incorporating trusted references while optimizing blog posts intelligently (rather than keyword stuffing) can significantly aid quality enhancements. Remember, it isn\'t about writing for Google here; we avoid tunnel vision focusing solely on algorithm-driven success rate, aiming at heart-touching human connections, building loyal reader bases, and sharing knowledge benefiting others!'],

                ['type' => 'heading', 'text' => 'Conclusion: Embracing AI in Blog Creation'],
                ['type' => 'p', 'text' => 'As we wrap up, let\'s remember the heart of blog creation is serving our readers. Whether a post was drafted by experts or AI like ChatGPT doesn\'t matter to Google algorithms as long as it\'s meaningful and high-quality.'],
                ['type' => 'p', 'text' => 'Through this valuable learning curve together, I hope you\'ve seen how well-implemented strategies can guide generative tools in delivering content mirroring human quality. Yet it often involves some trial &amp; error phases, but trust me — persistence practised alongside continuous improvements results in rewarding hurdles!'],
                ['type' => 'p', 'text' => 'Additionally, perhaps most importantly, proofreading every piece before publishing hugely influences audience perceptions, establishing professional credibility. Why? Well, even minor oversights could potentially undermine reader expectations, turning away prospective subscribers; hence, maintain meticulous checkpoints for flawless publications!'],
                ['type' => 'p', 'text' => 'So here goes my fellow SaaS marketing managers: Embrace technology enhancement tools responsibly, always keeping end-user perspectives focal while constantly striving towards better communication standards, offering insightful, pleasing reads across widespread digital platforms!'],

                ['type' => 'heading', 'text' => 'Afterword: The AI Behind This Article'],
                ['type' => 'figure', 'image' => 'blog-ai.webp', 'alt' => 'AI-detector result: the article is reported as human by GPTZero, OpenAI, Writer, Crossplag, Copyleaks, Sapling, ContentAtScale and ZeroGPT'],
                ['type' => 'p', 'text' => '<strong>Let\'s be clear:</strong> ChatGPT wrote this article and generated the hero image. It combined my personal experience, knowledge, and research. From the initial notes to finish, it took just 37 minutes.'],
                ['type' => 'p', 'text' => 'Even though it was made by AI, no detection tools could tell. The only thing used was OpenAI\'s Chat API, no other external tools.'],
                ['type' => 'p', 'text' => 'It shows how AI can help in making content interesting and relevant. It\'s a new chapter in how we create and share information.'],
            ],
        ];

        $latestBlogs = [
            ['title' => 'Resume Mistakes that Could Cost You Your Dream Job',   'image' => 'blog-4.webp', 'date' => '20 July, 2024'],
            ['title' => 'Why Hands-On Practical Matter More Than Certificates', 'image' => 'blog-3.webp', 'date' => '20 July, 2024'],
            ['title' => 'How to Prepare for Your First Technical Interview',    'image' => 'blog-2.webp', 'date' => '20 July, 2024'],
        ];

        $tags = ['Education', 'HR Training', 'Online', 'Learn', 'Course', 'LMS'];

        // Icons ship as brand-coloured circles in assets/images/blog/. They were
        // exported as "Group (11..16).png"; renamed, because a literal space in
        // a URL is only rescued by the browser's own encoding.
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
                            <img src="{{ asset('assets/images/blog/' . $blog['image']) }}"
                                 alt="{{ $blog['title'] }}"
                                 width="982" height="520">
                        </figure>

                        <div class="hm-post__content">

                            <div class="hm-post__meta">
                                <span class="hm-post__meta-item">
                                    <img class="hm-post__avatar"
                                         src="{{ asset('assets/images/blog/' . $blog['avatar']) }}"
                                         alt="" aria-hidden="true">
                                    {{ $blog['author'] }}
                                </span>

                                <span class="hm-post__meta-item">
                                    <img class="hm-post__meta-icon"
                                         src="{{ asset('assets/images/blog/calendar.png') }}"
                                         alt="" aria-hidden="true">
                                    <time datetime="{{ $blog['datetime'] }}">{{ $blog['date'] }}</time>
                                </span>
                            </div>

                            <h2 class="hm-post__title">{{ $blog['title'] }}</h2>

                            <div class="hm-post__body">
                            @foreach ($blog['content'] as $block)
                                @switch($block['type'])
                                    @case('heading')
                                        <h3>{{ $block['text'] }}</h3>
                                        @break

                                    @case('figure')
                                        <figure class="hm-post__embed">
                                            <img src="{{ asset('assets/images/blog/' . $block['image']) }}"
                                                 alt="{{ $block['alt'] }}" loading="lazy">
                                        </figure>
                                        @break

                                    @default
                                        {{-- Unescaped so the inline <a>/<strong> render. The copy is
                                             author-controlled above; sanitise it here once it comes
                                             from the database. --}}
                                        <p>{!! $block['text'] !!}</p>
                                @endswitch
                            @endforeach
                            </div>

                        </div>

                    </article>
                </div>

                {{-- ============================= SIDEBAR ============================= --}}
                <div class="col-12 col-lg-3">
                    <aside class="hm-side">

                        <section class="hm-side__card" aria-labelledby="hmSideLatest">
                            <h2 class="hm-side__heading" id="hmSideLatest">The Latest</h2>

                            <ul class="hm-side__list">
                                @foreach ($latestBlogs as $latest)
                                    <li>
                                        <a class="hm-side__item" href="{{ route('frontend.blog-details') }}">
                                            <img src="{{ asset('assets/images/blog/' . $latest['image']) }}"
                                                 alt="" aria-hidden="true" loading="lazy">
                                            <h3 class="hm-side__item-title">{{ $latest['title'] }}</h3>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <a class="hm-side__seeall" href="{{ route('frontend.blog') }}">See all</a>
                        </section>

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
