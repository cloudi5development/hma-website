{{-- ============================ ADMIN SIDEBAR ============================
     Module tree. Stroke (outline) SVG icons, defined once in $icons and drawn
     through the $ic() helper. Group parents expand inline; in rail (collapsed)
     mode they reveal a flyout on hover.
--}}
@php
    // Inner paths only — $ic() wraps them in a consistent stroke <svg>.
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/>',
        'courses'   => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v4.5c0 1 2.7 2.5 6 2.5s6-1.5 6-2.5V12"/>',
        'department'=> '<path d="M6 22V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v18"/><path d="M4 22h16M9.5 7h1M13.5 7h1M9.5 11h1M13.5 11h1M10 22v-4h4v4"/>',
        'category'  => '<path d="M20.6 13.4 12 22l-8-8V4h10l6.6 6.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.1"/>',
        'book'      => '<path d="M12 6.5C10.5 5 8 4.5 4 5v13c4-.5 6.5 0 8 1.5 1.5-1.5 4-2 8-1.5V5c-4-.5-6.5 0-8 1.5Z"/><path d="M12 6.5v13"/>',
        'sections'  => '<rect x="3" y="3" width="18" height="18" rx="2.2"/><path d="M3 9h18M9 21V9"/>',
        'hero'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6.5 9.5h5M6.5 12.5h3"/><rect x="14.5" y="8.5" width="4" height="7" rx="1"/>',
        'partners'  => '<path d="M12 3 5 6v5c0 4.6 3 7.6 7 9 4-1.4 7-4.4 7-9V6l-7-3Z"/><path d="M9.3 12l1.8 1.8 3.4-3.8"/>',
        'counters'  => '<path d="M3 3v18h18"/><rect x="7" y="11" width="3" height="6" rx="1"/><rect x="12" y="7" width="3" height="10" rx="1"/><rect x="17" y="13" width="3" height="4" rx="1"/>',
        'events'    => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9.5h18M8 3v3M16 3v3"/>',
        'stories'   => '<circle cx="12" cy="9" r="5"/><path d="M9 13.4 8 22l4-2.4L16 22l-1-8.6"/>',
        'journey'   => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4"/>',
        'testimonials'=> '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 9h6M8 12.5h4"/>',
        'faq'       => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.2a2.5 2.5 0 0 1 4.8.8c0 1.7-2.4 2.3-2.4 3.5M12 17h.01"/>',
        'enquiries' => '<path d="M5.5 5h13l1.5 8v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4l1.5-8Z"/><path d="M4 13h4l1.4 3h5.2L20 13"/>',
        'course-enquiry' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M8.5 10h7M8.5 14h5"/>',
        'contact-enquiry'=> '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.6 7 8.4 6 8.4-6"/>',
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
        <p class="app-sidebar__heading">Catalog</p>

        {{-- Courses (group) --}}
        @php $coursesOpen = request()->routeIs('backend.departments.*', 'backend.categories.*', 'backend.courses.*'); @endphp
        <div class="app-nav__item {{ $coursesOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('courses') !!}
                <span class="app-nav__label">Courses</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.departments.*') ? 'is-active' : '' }}" href="{{ route('backend.departments.index') }}">{!! $ic('department') !!}<span>Departments</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.categories.*') ? 'is-active' : '' }}" href="{{ route('backend.categories.index') }}">{!! $ic('category') !!}<span>Categories</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.courses.*') ? 'is-active' : '' }}" href="{{ route('backend.courses.index') }}">{!! $ic('book') !!}<span>Courses</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Courses</div>
                <a href="{{ route('backend.departments.index') }}">Departments</a><a href="{{ route('backend.categories.index') }}">Categories</a><a href="{{ route('backend.courses.index') }}">Courses</a>
            </div>
        </div>

        {{-- ---- WEBSITE CONTENT ---- --}}
        <p class="app-sidebar__heading">Website Content</p>

        {{-- Sections (group) --}}
        @php $sectionsOpen = request()->routeIs('backend.hero.*', 'backend.partners.*', 'backend.counters.*', 'backend.events.*', 'backend.success-stories.*', 'backend.reels.*', 'backend.testimonials.*', 'backend.faqs.*'); @endphp
        <div class="app-nav__item {{ $sectionsOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('sections') !!}
                <span class="app-nav__label">Sections</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.hero.*') ? 'is-active' : '' }}" href="{{ route('backend.hero.index') }}">{!! $ic('hero') !!}<span>Hero Section</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.partners.*') ? 'is-active' : '' }}" href="{{ route('backend.partners.index') }}">{!! $ic('partners') !!}<span>Trusted Partners</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.counters.*') ? 'is-active' : '' }}" href="{{ route('backend.counters.index') }}">{!! $ic('counters') !!}<span>Counters</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.events.*') ? 'is-active' : '' }}" href="{{ route('backend.events.index') }}">{!! $ic('events') !!}<span>Upcoming Events</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.success-stories.*') ? 'is-active' : '' }}" href="{{ route('backend.success-stories.index') }}">{!! $ic('stories') !!}<span>Success Stories</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.reels.*') ? 'is-active' : '' }}" href="{{ route('backend.reels.index') }}">{!! $ic('journey') !!}<span>Our Journey</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.testimonials.*') ? 'is-active' : '' }}" href="{{ route('backend.testimonials.index') }}">{!! $ic('testimonials') !!}<span>Testimonials</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.faqs.*') ? 'is-active' : '' }}" href="{{ route('backend.faqs.index') }}">{!! $ic('faq') !!}<span>FAQ</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Sections</div>
                <a href="{{ route('backend.hero.index') }}">Hero Section</a><a href="{{ route('backend.partners.index') }}">Trusted Partners</a><a href="{{ route('backend.counters.index') }}">Counters</a><a href="{{ route('backend.events.index') }}">Upcoming Events</a>
                <a href="{{ route('backend.success-stories.index') }}">Success Stories</a><a href="{{ route('backend.reels.index') }}">Our Journey</a><a href="{{ route('backend.testimonials.index') }}">Testimonials</a><a href="{{ route('backend.faqs.index') }}">FAQ</a>
            </div>
        </div>

        <div class="app-nav__item">
            <a class="app-nav__link {{ request()->routeIs('backend.blogs.*') ? 'is-active' : '' }}" href="{{ route('backend.blogs.index') }}">{!! $ic('blog') !!}<span class="app-nav__label">Blog</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Blog</div></div>
        </div>

        {{-- ---- LEADS ---- --}}
        <p class="app-sidebar__heading">Leads</p>

        {{-- Enquiries (group) --}}
        @php
            $enquiriesOpen = request()->routeIs('backend.contact-enquiries.*', 'backend.course-enquiries.*');
            $newEnquiries  = \App\Models\ContactEnquiry::where('status', 'New')->count()
                           + \App\Models\CourseEnquiry::where('status', 'New')->count();
        @endphp
        <div class="app-nav__item {{ $enquiriesOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('enquiries') !!}
                <span class="app-nav__label">Enquiries</span>
                @if ($newEnquiries) <span class="app-nav__badge">{{ $newEnquiries }}</span> @endif
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.course-enquiries.*') ? 'is-active' : '' }}" href="{{ route('backend.course-enquiries.index') }}">{!! $ic('course-enquiry') !!}<span>Course Enquiry</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.contact-enquiries.*') ? 'is-active' : '' }}" href="{{ route('backend.contact-enquiries.index') }}">{!! $ic('contact-enquiry') !!}<span>Contact Enquiry</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Enquiries</div>
                <a href="{{ route('backend.course-enquiries.index') }}">Course Enquiry</a><a href="{{ route('backend.contact-enquiries.index') }}">Contact Enquiry</a>
            </div>
        </div>

        {{-- ---- SYSTEM ---- --}}
        <p class="app-sidebar__heading">System</p>

        {{-- Settings (group) --}}
        @php $settingsOpen = request()->routeIs('backend.settings.*'); @endphp
        <div class="app-nav__item {{ $settingsOpen ? 'is-open' : '' }}" data-group>
            <a class="app-nav__link" role="button" tabindex="0">
                {!! $ic('settings') !!}
                <span class="app-nav__label">Settings</span>
                <span class="app-nav__caret">{!! $ic('chevron') !!}</span>
            </a>
            <ul class="app-nav__sub">
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.general') ? 'is-active' : '' }}" href="{{ route('backend.settings.general') }}">{!! $ic('general') !!}<span>General</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.contact') ? 'is-active' : '' }}" href="{{ route('backend.settings.contact') }}">{!! $ic('contact') !!}<span>Contact</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.social') ? 'is-active' : '' }}" href="{{ route('backend.settings.social') }}">{!! $ic('social') !!}<span>Social Media</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.email') ? 'is-active' : '' }}" href="{{ route('backend.settings.email') }}">{!! $ic('email') !!}<span>Email / SMTP</span></a></li>
                <li><a class="app-nav__sublink {{ request()->routeIs('backend.settings.seo') ? 'is-active' : '' }}" href="{{ route('backend.settings.seo') }}">{!! $ic('seo') !!}<span>SEO Defaults</span></a></li>
            </ul>
            <div class="app-nav__flyout">
                <div class="app-nav__flyout-title">Settings</div>
                <a href="{{ route('backend.settings.general') }}">General</a><a href="{{ route('backend.settings.contact') }}">Contact</a><a href="{{ route('backend.settings.social') }}">Social Media</a>
                <a href="{{ route('backend.settings.email') }}">Email / SMTP</a><a href="{{ route('backend.settings.seo') }}">SEO Defaults</a>
            </div>
        </div>

        <div class="app-nav__item">
            <a class="app-nav__link" href="#">{!! $ic('seo') !!}<span class="app-nav__label">SEO</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">SEO</div></div>
        </div>

        <div class="app-nav__item">
            <a class="app-nav__link" href="#">{!! $ic('users') !!}<span class="app-nav__label">Users</span></a>
            <div class="app-nav__flyout"><div class="app-nav__flyout-title">Users</div></div>
        </div>

    </nav>
</aside>

{{-- Dimmer behind the off-canvas sidebar on mobile --}}
<div class="app-backdrop" id="appBackdrop" aria-hidden="true"></div>
