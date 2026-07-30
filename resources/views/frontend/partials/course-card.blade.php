{{--
|--------------------------------------------------------------------------
| Course card (shared component)
|--------------------------------------------------------------------------
|
| Used by the home page's "Popular Courses" grid and by the courses listing
| page. Render one card per course, inside your own column:
|
|   @foreach ($courses as $i => $course)
|       <div class="col-lg-3 col-md-6">
|           @include('frontend.partials.course-card', ['course' => $course, 'i' => $i])
|       </div>
|   @endforeach
|
| and push its stylesheet from the page (the head is already rendered by the
| time an @include runs, so @push('styles') cannot work from in here):
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/courses.css') }}?v={{ filemtime(public_path('assets/css/frontend/courses.css')) }}">
|
| $course keys:
|   img        thumbnail filename in assets/images/courses/
|   badge      category pill text
|   title      course name
|   rating     e.g. '4.5'
|   duration   optional, defaults to '3 months'
|   mode       optional, defaults to 'On-Campus Learning'
|   certificate optional, defaults to 'Industry Certificate'
|   url        optional, defaults to '#'
|
| $i is optional and only drives the stagger delay on the home page.
|
| Note: the .hm-anim reveal is a HOME-PAGE enhancement — those rules live in
| home.css alongside the observer that adds .is-in. On any page that does not
| load home.css the class is inert, so the card renders visible rather than
| staying stuck at opacity 0.
--}}
@php
    $i ??= 0;
@endphp

<article class="hm-course hm-anim hm-anim--up hm-anim--d{{ ($i % 4) + 1 }}">
    <div class="hm-course__thumb">
        {{-- 'img_url' is a ready-built URL (DB-driven cards); 'img' is a bare
             filename in assets/images/courses/ (legacy/static callers). --}}
        <img src="{{ $course['img_url'] ?? asset('assets/images/courses/' . $course['img']) }}"
             alt="{{ $course['title'] }} course thumbnail" loading="lazy">
    </div>

    <div class="hm-course__body">
        <div class="hm-course__tags">
            <span class="hm-course__badge">{{ $course['badge'] }}</span>
            <span class="hm-course__rating">
                <img src="{{ asset('assets/images/courses/star.png') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                {{ $course['rating'] }}
            </span>
        </div>

        <h3 class="hm-course__title">{{ $course['title'] }}</h3>

        <ul class="hm-course__meta">
            <li class="hm-course__meta-row">
                <span class="hm-course__meta-item">
                    <img src="{{ asset('assets/images/courses/iconamoon_clock-light.png') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                    {{ $course['duration'] ?? '3 months' }}
                </span>
                <span class="hm-course__meta-item">
                    <img src="{{ asset('assets/images/courses/school.png') }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
                    {{ $course['mode'] ?? 'On-Campus Learning' }}
                </span>
            </li>
            <li class="hm-course__meta-row">
                <span class="hm-course__meta-item">
                    {{-- No certificate icon ships in assets/images/courses/, so this
                         one row falls back to the icon font. --}}
                    <i class="fa-regular fa-circle-check" aria-hidden="true"></i>
                    {{ $course['certificate'] ?? 'Industry Certificate' }}
                </span>
            </li>
        </ul>

        <a class="hm-course__btn" href="{{ $course['url'] ?? '#' }}">
            <span>View Course</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</article>
