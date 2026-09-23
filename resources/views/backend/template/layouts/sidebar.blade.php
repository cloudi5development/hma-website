{{-- ============================ ADMIN SIDEBAR ============================
     Module tree. Stroke (outline) SVG icons, defined once in $icons and drawn
     through the $ic() helper. Group parents expand inline; in rail (collapsed)
     mode they reveal a flyout on hover.
--}}
@php
    // Inner paths only — $ic() wraps them in a consistent stroke <svg>.
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/>',
        // A checklist on a clipboard — assessment, then readiness.
        'placement' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="m8.8 11.5 1.6 1.6 3.4-3.4M8.8 17h6.4"/>',
        'courses'   => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v4.5c0 1 2.7 2.5 6 2.5s6-1.5 6-2.5V12"/>',
        'department'=> '<path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M4 22h16M9.5 7h1M13.5 7h1M9.5 11h1M13.5 11h1M10 22v-4h4v4"/>',
        'category'  => '<path d="M20.6 13.4 12 22l-8-8V4h10l6.6 6.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.1"/>',
        'book'      => '<path d="M12 6.5C10.5 5 8 4.5 4 5v13c4-.5 6.5 0 8 1.5 1.5-1.5 4-2 8-1.5V5c-4-.5-6.5 0-8 1.5Z"/><path d="M12 6.5v13"/>',
        'schedule'  => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/><path d="M11.5 13h5M11.5 16.5h3"/>',
        'sections'  => '<rect x="3" y="3" width="18" height="18" rx="2.2"/><path d="M3 9h18M9 21V9"/>',
        'hero'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6.5 9.5h5M6.5 12.5h3"/><rect x="14.5" y="8.5" width="4" height="7" rx="1"/>',
        'partners'  => '<path d="M12 3 5 6v5c0 4.6 3 7.6 7 9 4-1.4 7-4.4 7-9V6l-7-3Z"/><path d="M9.3 12l1.8 1.8 3.4-3.8"/>',
        'counters'  => '<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6" rx="1"/><rect x="12" y="7" width="3" height="10" rx="1"/><rect x="17" y="13" width="3" height="4" rx="1"/>',
        'events'    => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/>',
        'stories'   => '<circle cx="12" cy="9" r="5"/><path d="M9 13.4 8 22l4-2.4L16 22l-1-8.6"/>',
        'journey'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
        'testimonials'=> '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h6M8 12.5h4"/>',
        'about'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6h.01"/>',
        'faq'       => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.3-2.4 3.5M12 17h.01"/>',
        'enquiries' => '<path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4l1.5-8Z"/><path d="M4 13h4l1.4 3h5.2L20 13"/>',
        'course-enquiry' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7M8.5 14h5"/>',
        'contact-enquiry'=> '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>',
        'event-registration' => '<path d="M4 8.5A2 2 0 0 0 6 6.5V5.5h12v1a2 2 0 0 0 0 4v1a2 2 0 0 0 0 4v1H6v-1a2 2 0 0 0-2-2Z"/><path d="M10 9v6" stroke-dasharray="1.6 2.2"/>',
        'forms'     => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'form-list' => '<path d="M8 6h12M8 12h12M8 18h12"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'form-new'  => '<path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14.5 3v4.5H19M12 11v6M9 14h6"/>',
        'content-pages' => '<path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14.5 3v4.5H19M8.5 12h7M8.5 15.5h7M8.5 19h4"/>',
        'legal'     => '<path d="M12 3v18M7 7l-4 6a4 4 0 0 0 8 0L7 7ZM17 7l-4 6a4 4 0 0 0 8 0l-4-6Z"/><path d="M5 21h14M7 7l10-2"/>',
        'blog'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'settings'  => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'general'   => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1.5 14h5M9.5 8h5M17.5 16h5"/>',
        'contact'   => '<path d="M6 3h3l1.5 5-2 1.4a12 12 0 0 0 6 6l1.4-2 5 1.5v3a2 2 0 0 1-2 2A17 17 0 0 1 4 5a2 2 0 0 1 2-2Z"/>',
        'social'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.2M8.6 13.4l6.8 4.2"/>',
        'email'     => '<circle cx="12" cy="12" r="4"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0V12a9 9 0 1 0-3.5 7.1"/>',
        'seo'       => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'users'     => '<circle cx="9" cy="8" r="3.4"/><path d="M3 20c0-3.3 2.7-5.4 6-5.4s6 2.1 6 5.4"/><path d="M16 4.6a3.4 3.4 0 0 1 0 6.8M21 20c0-2.6-1.5-4.3-4-5"/>',
        'chevron'   => '<path d="m6 9 6 6 6-6"/>',
    ];

    $ic = fn ($name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . ($icons[$name] ?? '') . '</svg>';

    $isDash = request()->routeIs('backend.dashboard');

    // Module permissions for the signed-in admin (Users → Module Access). A menu
    // entry they cannot open is not rendered at all — and a group or heading whose
    // every child is hidden goes with it, so nobody is left staring at an empty
    // "Leads" label. The admin.module middleware enforces the same list on the
    // way in, so hiding here is presentation, not the guard.
    $can    = fn ($module) => \App\Support\AdminAuth::can($module);
    $canAny = fn (...$modules) => \App\Support\AdminAuth::canAny(...$modules);
    $isMain = \App\Support\AdminAuth::isSuperAdmin();

    $sectionModules = ['hero', 'partners', 'counters', 'events', 'success-stories', 'reels', 'testimonials', 'faqs', 'about-sections'];
@endphp

<aside class="app-sidebar" id="appSidebar">

    <div class="app-sidebar__brand">
        <img class="app-sidebar__brand-mark" src="{{ asset('backend/template/images/favicon.png') }}" alt="">
        <img class="app-sidebar__brand-logo" src="{{ asset('backend/template/images/logo-text.png') }}" alt="HireMinds Academy">
    </div>

    <nav class="app-sidebar__nav">

        {{-- ---- MAIN ---- --}}
        <p class="app-sidebar__heading">Main</p>

        <div class="app-nav__item">
            <a href="{{ route('backend.dashboard') }}" class="app-nav__link {{ $isDash ? 'is-active' : '' }}">
                {!! $ic('dashboard') !!}
                <span class="app-nav__label">Dashboard</span>
            </a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Dashboard</div></div>
        </div>

        {{-- ---- CATALOG ---- --}}
        @if ($canAny('departments', 'categories', 'courses', 'schedules'))
        <p class="app-sidebar__heading">Catalog</p>

        {{-- Courses (group) --}}
        @php $coursesOpen = request()->routeIs('backend.departments.*', 'backend.categories.*', 'backend.courses.*', 'backend.schedules.*'); @endphp
        <div class="app-nav__item {{ $coursesOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('courses') !!}
                <span class="app-nav__label">Courses</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                @if ($can('departments'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.departments.*') ? 'is-active' : '' }}" href="{{ route('backend.departments.index') }}">{!! $ic('department') !!}<span>Departments</span></a></li>@endif
                @if ($can('categories'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.categories.*') ? 'is-active' : '' }}" href="{{ route('backend.categories.index') }}">{!! $ic('category') !!}<span>Categories</span></a></li>@endif
                @if ($can('courses'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.courses.*') ? 'is-active' : '' }}" href="{{ route('backend.courses.index') }}">{!! $ic('book') !!}<span>Courses</span></a></li>@endif
                @if ($can('schedules'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.schedules.*') ? 'is-active' : '' }}" href="{{ route('backend.schedules.index') }}">{!! $ic('schedule') !!}<span>Schedule</span></a></li>@endif
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Courses</div>
                @if ($can('departments'))<a href="{{ route('backend.departments.index') }}">Departments</a>@endif
                @if ($can('categories'))<a href="{{ route('backend.categories.index') }}">Categories</a>@endif
                @if ($can('courses'))<a href="{{ route('backend.courses.index') }}">Courses</a>@endif
                @if ($can('schedules'))<a href="{{ route('backend.schedules.index') }}">Schedule</a>@endif
            </div>
        </div>
        @endif

        {{-- ---- WEBSITE CONTENT ---- --}}
        @if ($canAny(...$sectionModules) || $can('placement-readiness') || $can('blogs'))
        <p class="app-sidebar__heading">Website Content</p>
        @endif

        {{-- Sections (group) --}}
        @if ($canAny(...$sectionModules))
        @php $sectionsOpen = request()->routeIs('backend.hero.*', 'backend.partners.*', 'backend.counters.*', 'backend.events.*', 'backend.success-stories.*', 'backend.reels.*', 'backend.testimonials.*', 'backend.faqs.*', 'backend.about-sections.*'); @endphp
        <div class="app-nav__item {{ $sectionsOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('sections') !!}
                <span class="app-nav__label">Sections</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                @if ($can('hero'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.hero.*') ? 'is-active' : '' }}" href="{{ route('backend.hero.index') }}">{!! $ic('hero') !!}<span>Hero Section</span></a></li>@endif
                @if ($can('partners'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.partners.*') ? 'is-active' : '' }}" href="{{ route('backend.partners.index') }}">{!! $ic('partners') !!}<span>Trusted Partners</span></a></li>@endif
                @if ($can('counters'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.counters.*') ? 'is-active' : '' }}" href="{{ route('backend.counters.index') }}">{!! $ic('counters') !!}<span>Counters</span></a></li>@endif
                @if ($can('events'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.events.*') ? 'is-active' : '' }}" href="{{ route('backend.events.index') }}">{!! $ic('events') !!}<span>Upcoming Events</span></a></li>@endif
                @if ($can('success-stories'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.success-stories.*') ? 'is-active' : '' }}" href="{{ route('backend.success-stories.index') }}">{!! $ic('stories') !!}<span>Success Stories</span></a></li>@endif
                @if ($can('reels'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.reels.*') ? 'is-active' : '' }}" href="{{ route('backend.reels.index') }}">{!! $ic('journey') !!}<span>Our Journey</span></a></li>@endif
                @if ($can('testimonials'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.testimonials.*') ? 'is-active' : '' }}" href="{{ route('backend.testimonials.index') }}">{!! $ic('testimonials') !!}<span>Testimonials</span></a></li>@endif
                @if ($can('faqs'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.faqs.*') ? 'is-active' : '' }}" href="{{ route('backend.faqs.index') }}">{!! $ic('faq') !!}<span>FAQ</span></a></li>@endif
                @if ($can('about-sections'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.about-sections.*') ? 'is-active' : '' }}" href="{{ route('backend.about-sections.index') }}">{!! $ic('about') !!}<span>About Us</span></a></li>@endif
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Sections</div>
                @if ($can('hero'))<a href="{{ route('backend.hero.index') }}">Hero Section</a>@endif
                @if ($can('partners'))<a href="{{ route('backend.partners.index') }}">Trusted Partners</a>@endif
                @if ($can('counters'))<a href="{{ route('backend.counters.index') }}">Counters</a>@endif
                @if ($can('events'))<a href="{{ route('backend.events.index') }}">Upcoming Events</a>@endif
                @if ($can('success-stories'))<a href="{{ route('backend.success-stories.index') }}">Success Stories</a>@endif
                @if ($can('reels'))<a href="{{ route('backend.reels.index') }}">Our Journey</a>@endif
                @if ($can('testimonials'))<a href="{{ route('backend.testimonials.index') }}">Testimonials</a>@endif
                @if ($can('faqs'))<a href="{{ route('backend.faqs.index') }}">FAQ</a>@endif
                @if ($can('about-sections'))<a href="{{ route('backend.about-sections.index') }}">About Us</a>@endif
            </div>
        </div>
        @endif

        @if ($can('placement-readiness'))
        <div class="app-nav__item">
            <a class="app-nav__link {{ request()->routeIs('backend.placement-readiness.*') ? 'is-active' : '' }}" href="{{ route('backend.placement-readiness.index') }}">{!! $ic('placement') !!}<span class="app-nav__label">Placement Readiness</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Placement Readiness</div></div>
        </div>
        @endif

        @if ($can('blogs'))
        <div class="app-nav__item">
            <a class="app-nav__link {{ request()->routeIs('backend.blogs.*') ? 'is-active' : '' }}" href="{{ route('backend.blogs.index') }}">{!! $ic('blog') !!}<span class="app-nav__label">Blog</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Blog</div></div>
        </div>
        @endif

        {{-- ---- LEADS ---- --}}
        @if ($canAny('course-enquiries', 'contact-enquiries', 'event-registrations'))
        <p class="app-sidebar__heading">Leads</p>

        {{-- Enquiries (group) --}}
        @php
            $enquiriesOpen = request()->routeIs('backend.contact-enquiries.*', 'backend.course-enquiries.*', 'backend.event-registrations.*');
            $newEnquiries  = \App\Models\ContactEnquiry::where('status', 'New')->count()
                           + \App\Models\CourseEnquiry::where('status', 'New')->count()
                           + \App\Models\EventRegistration::where('status', 'New')->count();
        @endphp
        <div class="app-nav__item {{ $enquiriesOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('enquiries') !!}
                <span class="app-nav__label">Enquiries</span>
                @if ($newEnquiries) <span class="app-nav__badge">{{ $newEnquiries }}</span> @endif
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                @if ($can('course-enquiries'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.course-enquiries.*') ? 'is-active' : '' }}" href="{{ route('backend.course-enquiries.index') }}">{!! $ic('course-enquiry') !!}<span>Course Enquiry</span></a></li>@endif
                @if ($can('contact-enquiries'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.contact-enquiries.*') ? 'is-active' : '' }}" href="{{ route('backend.contact-enquiries.index') }}">{!! $ic('contact-enquiry') !!}<span>Contact Enquiry</span></a></li>@endif
                @if ($can('event-registrations'))<li><a class="app-nav__sublink {{ request()->routeIs('backend.event-registrations.*') ? 'is-active' : '' }}" href="{{ route('backend.event-registrations.index') }}">{!! $ic('event-registration') !!}<span>Event Registration</span></a></li>@endif
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Enquiries</div>
                @if ($can('course-enquiries'))<a href="{{ route('backend.course-enquiries.index') }}">Course Enquiry</a>@endif
                @if ($can('contact-enquiries'))<a href="{{ route('backend.contact-enquiries.index') }}">Contact Enquiry</a>@endif
                @if ($can('event-registrations'))<a href="{{ route('backend.event-registrations.index') }}">Event Registration</a>@endif
            </div>
        </div>
        @endif

        {{-- Forms — the dynamic form builder. Sits under Leads because that is
             what its responses are, and it carries the same "N new" badge the
             Enquiries group does. --}}
        @if ($can('forms'))
        @php
            $formsOpen = request()->routeIs('backend.forms.*');
            $newResponses = \App\Models\FormResponse::where('status', 'New')->count();
        @endphp
        <div class="app-nav__item {{ $formsOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('forms') !!}
                <span class="app-nav__label">Forms</span>
                @if ($newResponses) <span class="app-nav__badge">{{ $newResponses }}</span> @endif
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            {{-- Two entries: the forms themselves, and everything they have
                 collected. Creating a form is a button above the All Forms
                 table rather than a menu item — it is something you do to that
                 list, not a place you go. --}}
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.forms.index', 'backend.forms.create', 'backend.forms.edit', 'backend.forms.show', 'backend.forms.preview') ? 'is-active' : '' }}"
                       href="{{ route('backend.forms.index') }}">{!! $ic('form-list') !!}<span>All Forms</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.forms.all-responses') || request()->routeIs('backend.forms.responses.*') ? 'is-active' : '' }}"
                       href="{{ route('backend.forms.all-responses') }}">{!! $ic('form-new') !!}<span>Responses</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Forms</div>
                <a href="{{ route('backend.forms.index') }}">All Forms</a>
                <a href="{{ route('backend.forms.all-responses') }}">Responses</a>
            </div>
        </div>
        @endif

        {{-- ---- CONTENT MANAGEMENT ---- --}}
        @if ($can('content-pages'))
        <p class="app-sidebar__heading">Content Management</p>

        @php
            $contentOpen = request()->routeIs('backend.content-pages.*');
            $currentKey  = request()->route('key');
            // Titles come from the rows, so renaming a page in the panel renames
            // its menu entry too. Ordered in PHP against PAGES rather than with a
            // raw ORDER BY CASE — that would tie the menu to one database driver
            // for no gain over two rows.
            $pageOrder    = array_keys(\App\Models\ContentPage::PAGES);
            $contentPages = \App\Models\ContentPage::whereIn('key', $pageOrder)
                ->get(['key', 'title'])
                ->sortBy(fn ($cp) => array_search($cp->key, $pageOrder, true))
                ->values();
        @endphp
        <div class="app-nav__item {{ $contentOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('content-pages') !!}
                <span class="app-nav__label">Pages</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                @foreach ($contentPages as $cp)
                    <li><a class="app-nav__sublink {{ $contentOpen && $currentKey === $cp->key ? 'is-active' : '' }}"
                           href="{{ route('backend.content-pages.edit', $cp->key) }}">{!! $ic('legal') !!}<span>{{ $cp->title }}</span></a></li>
                @endforeach
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Pages</div>
                @foreach ($contentPages as $cp)
                    <a href="{{ route('backend.content-pages.edit', $cp->key) }}">{{ $cp->title }}</a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ---- SYSTEM ---- --}}
        @if ($canAny('settings', 'seo-pages') || $isMain)
        <p class="app-sidebar__heading">System</p>
        @endif

        {{-- Settings (group) --}}
        @if ($can('settings'))
        @php $settingsOpen = request()->routeIs('backend.settings.*'); @endphp
        <div class="app-nav__item {{ $settingsOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('settings') !!}
                <span class="app-nav__label">Settings</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.general') ? 'is-active' : '' }}" href="{{ route('backend.settings.general') }}">{!! $ic('general') !!}<span>General</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.logo') ? 'is-active' : '' }}" href="{{ route('backend.settings.logo') }}">{!! $ic('general') !!}<span>Logo</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.contact') ? 'is-active' : '' }}" href="{{ route('backend.settings.contact') }}">{!! $ic('contact') !!}<span>Contact</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.social') ? 'is-active' : '' }}" href="{{ route('backend.settings.social') }}">{!! $ic('social') !!}<span>Social Media</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.email') ? 'is-active' : '' }}" href="{{ route('backend.settings.email') }}">{!! $ic('email') !!}<span>Email / SMTP</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.seo') ? 'is-active' : '' }}" href="{{ route('backend.settings.seo') }}">{!! $ic('seo') !!}<span>SEO Defaults</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.recaptcha') ? 'is-active' : '' }}" href="{{ route('backend.settings.recaptcha') }}">{!! $ic('seo') !!}<span>reCAPTCHA</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Settings</div>
                <a href="{{ route('backend.settings.general') }}">General</a><a href="{{ route('backend.settings.logo') }}">Logo</a><a href="{{ route('backend.settings.contact') }}">Contact</a><a href="{{ route('backend.settings.social') }}">Social Media</a>
                <a href="{{ route('backend.settings.email') }}">Email / SMTP</a><a href="{{ route('backend.settings.seo') }}">SEO Defaults</a><a href="{{ route('backend.settings.recaptcha') }}">reCAPTCHA</a>
            </div>
        </div>
        @endif

        @if ($can('seo-pages'))
        <div class="app-nav__item">
            <a class="app-nav__link {{ request()->routeIs('backend.seo-pages.*') ? 'is-active' : '' }}"
               href="{{ route('backend.seo-pages.index') }}">{!! $ic('seo') !!}<span class="app-nav__label">SEO</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">SEO</div>
                <a href="{{ route('backend.seo-pages.index') }}">Page SEO</a></div>
        </div>
        @endif

        {{-- Users is the main admin's alone — creating accounts and granting
             module access lives here, so it is never shown to anyone else. --}}
        @if ($isMain)
        <div class="app-nav__item">
            <a class="app-nav__link {{ request()->routeIs('backend.users.*') ? 'is-active' : '' }}"
               href="{{ route('backend.users.index') }}">{!! $ic('users') !!}<span class="app-nav__label">Users</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Users</div>
                <a href="{{ route('backend.users.index') }}">All Users</a></div>
        </div>
        @endif

    </nav>
</aside>

{{-- Dimmer behind the off-canvas sidebar on mobile --}}
<div class="app-backdrop" id="appBackdrop" aria-hidden="true"></div>
