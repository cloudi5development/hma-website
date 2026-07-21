{{-- Frontend site header — global floating navbar, reused on every frontend page.
     Styles: assets/css/frontend/navbar.css · Behaviour: assets/js/frontend/navbar.js
     (both loaded site-wide via layouts/common-css and layouts/common-js). --}}
@php
    // Fed by AppServiceProvider's view composer (departments → their active
    // categories). Mapped to the exact column shape this markup expects, so the
    // mega-menu design is unchanged; only the data source moved to the database.
    $courseMenu = collect($megaDepartments ?? [])->map(fn ($dept) => [
        'title'     => $dept->name,
        'image_url' => $dept->image_url,
        'items'     => $dept->categories->map(fn ($cat) => [
            'label'    => $cat->name,
            'url'      => route('frontend.courses', ['category' => $cat->slug]),
            'featured' => (bool) $cat->is_featured,
        ])->all(),
    ])->all();
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

                {{-- Courses: the label is a real link to the courses page, and the
                     caret beside it is what opens the mega-dropdown. It used to be
                     a single <button>, so clicking "Courses" could only open the
                     panel — it never went anywhere. --}}
                <li class="hm-nav-item hm-nav-item--mega">
                    {{-- One @class for both: a literal class="" alongside @class
                         emits a second class attribute, and the browser keeps only
                         the first — which silently dropped the active state. --}}
                    <a @class([
                           'hm-mega-toggle',
                           'active' => request()->routeIs('frontend.courses')
                                    || request()->routeIs('frontend.course-details'),
                       ])
                       href="{{ route('frontend.courses') }}">Courses</a>

                    <button type="button" class="hm-mega-caret" id="hmCoursesToggle"
                            aria-haspopup="true" aria-expanded="false" aria-controls="hmCoursesMenu"
                            aria-label="Show course categories">
                        <i class="fa-solid fa-chevron-down hm-mega-toggle__caret" aria-hidden="true"></i>
                    </button>

                    <div class="hm-mega" id="hmCoursesMenu" aria-label="Course categories">
                        <div class="hm-mega__grid">
                            @foreach ($courseMenu as $column)
                                <div class="hm-mega__col">
                                    <a class="hm-mega__thumb" href="{{ route('frontend.courses') }}" tabindex="-1" aria-hidden="true">
                                        <img src="{{ $column['image_url'] ?? asset('assets/images/Header/domain-1.webp') }}"
                                             alt="{{ $column['title'] }} courses" width="240" height="110" loading="lazy">
                                    </a>
                                    <h3 class="hm-mega__title">{{ $column['title'] }}</h3>
                                    <ul class="hm-mega__list">
                                        @foreach ($column['items'] as $item)
                                            <li>
                                                <a href="{{ $item['url'] ?? '#' }}" @class(['is-featured' => $item['featured'] ?? false])>{{ $item['label'] }}</a>
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
