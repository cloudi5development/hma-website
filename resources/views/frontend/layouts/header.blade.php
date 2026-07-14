{{-- Frontend site header — global floating navbar, reused on every frontend page.
     Styles: assets/css/frontend/navbar.css · Behaviour: assets/js/frontend/navbar.js
     (both loaded site-wide via layouts/common-css and layouts/common-js). --}}
@php
    // Courses mega-menu columns. Edit here to change categories/links.
    $courseMenu = [
        ['title' => 'Technical', 'image' => 'domain-1.webp', 'items' => [
            ['label' => 'IT & Software', 'featured' => true],
            ['label' => 'Cloud & DevOps'],
            ['label' => 'Data & AI'],
            ['label' => 'Cyber Security'],
            ['label' => 'Engineering'],
        ]],
        ['title' => 'Non-technical', 'image' => 'domain-2.webp', 'items' => [
            ['label' => 'Communication'],
            ['label' => 'Leadership'],
            ['label' => 'HR & Behavioral'],
            ['label' => 'Sales'],
            ['label' => 'Early Career'],
            ['label' => 'Trainer'],
        ]],
        ['title' => 'Business', 'image' => 'domain-3.webp', 'items' => [
            ['label' => 'Project Management'],
            ['label' => 'Finance'],
            ['label' => 'Operations'],
            ['label' => 'Digital Marketing'],
        ]],
        ['title' => 'Industry', 'image' => 'domain-4.webp', 'items' => [
            ['label' => 'Manufacturing'],
            ['label' => 'Healthcare'],
            ['label' => 'Banking'],
            ['label' => 'Hospitality'],
            ['label' => 'Energy'],
        ]],
    ];
@endphp
<header>
    <nav class="hm-navbar" id="hmNavbar" aria-label="Primary navigation">
        <a class="hm-navbar__logo" href="{{ route('frontend.index') }}" aria-label="Hire Minds Academy — home">
            <img src="{{ asset('assets/images/branding/logo.png') }}" alt="Hire Minds Academy" width="150" height="44">
        </a>

        <button class="hm-navbar__toggle" id="hmNavToggle" type="button"
                aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="hmNavMenu">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <div class="hm-navbar__menu" id="hmNavMenu">
            <ul class="hm-navbar__links">
                <li><a href="{{ route('frontend.index') }}" @class(['active' => request()->routeIs('frontend.index')])>Home</a></li>
                <li><a href="{{ route('frontend.about-us') }}" @class(['active' => request()->routeIs('frontend.about-us')])>About Us</a></li>

                {{-- Courses mega-dropdown (opens on click) --}}
                <li class="hm-nav-item hm-nav-item--mega">
                    <button type="button" class="hm-mega-toggle" id="hmCoursesToggle"
                            aria-haspopup="true" aria-expanded="false" aria-controls="hmCoursesMenu">
                        Courses <i class="fa-solid fa-chevron-down hm-mega-toggle__caret" aria-hidden="true"></i>
                    </button>

                    <div class="hm-mega" id="hmCoursesMenu" aria-label="Course categories">
                        <div class="hm-mega__grid">
                            @foreach ($courseMenu as $column)
                                <div class="hm-mega__col">
                                    <a class="hm-mega__thumb" href="#" tabindex="-1" aria-hidden="true">
                                        <img src="{{ asset('assets/images/Header/'.$column['image']) }}"
                                             alt="{{ $column['title'] }} courses" width="240" height="110" loading="lazy">
                                    </a>
                                    <h3 class="hm-mega__title">{{ $column['title'] }}</h3>
                                    <ul class="hm-mega__list">
                                        @foreach ($column['items'] as $item)
                                            <li>
                                                <a href="#" @class(['is-featured' => $item['featured'] ?? false])>{{ $item['label'] }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </li>

                <li><a href="{{ route('frontend.testimonials') }}" @class(['active' => request()->routeIs('frontend.testimonials')])>Testimonials</a></li>
                <li><a href="{{ route('frontend.blog') }}" @class(['active' => request()->routeIs('frontend.blog*')])>Blog</a></li>
                <li><a href="{{ route('frontend.contact-us') }}" @class(['active' => request()->routeIs('frontend.contact-us')])>Contact Us</a></li>
            </ul>
            <a class="hm-navbar__cta" href="{{ route('frontend.contact-us') }}">
                Let's Talk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </nav>
</header>
