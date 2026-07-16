{{--
|--------------------------------------------------------------------------
| Career Success / reel slider (shared section)
|--------------------------------------------------------------------------
|
| Used by the home page and the testimonials page:
|
|   @include('frontend.partials.career-success')
|
| The two pages word the header differently, so the copy is optional @include
| data — omit it and you get the home page's wording:
|
|   @include('frontend.partials.career-success', [
|       'csLabel' => 'Career Success',
|       'csTitle' => 'Turning Learning Into Career Success',
|       'csDesc'  => 'Discover how our learners ...',
|   ])
|
| Push its stylesheet from the page (the head is already rendered by the time
| an @include runs, so @push('styles') cannot work from in here):
|
|   <link rel="stylesheet" href="{{ asset('assets/css/frontend/career-success.css') }}">
|
| The Swiper init ships with this partial (see the bottom), so the component
| carries its own behaviour wherever it is dropped in.
|
| Note: the .hm-anim / data-io reveal is a HOME-PAGE enhancement — those rules
| live in home.css alongside the observer that adds .is-in. On any page that
| does not load home.css the classes are inert, so the section simply renders
| visible rather than staying stuck at opacity 0.
--}}
    @php
        // Covers live in public/assets/images/our-journey/. Replace the 'url'
        // values with the real Instagram Reel links whenever they are ready.
        $reels = [
            ['image' => 'reel-1.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'A mentoring session at Hire Minds Academy'],
            ['image' => 'reel-2.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Inside a Hire Minds Academy classroom'],
            ['image' => 'reel-3.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Learners collaborating on a live project'],
            ['image' => 'reel-4.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'A hands-on workshop moment'],
            ['image' => 'reel-5.webp', 'url' => 'https://www.instagram.com/hireminds_academy/', 'title' => 'Talent Acquisition and HR Recruitment training'],
        ];
    @endphp
    <section class="hm-reels" id="our-journey" data-io aria-labelledby="hmReelsTitle">

        {{-- Same slow-rotating hero background --}}
        <div class="hm-reels__bg" aria-hidden="true">
            <img src="{{ asset('assets/images/Hero-section/hero-bg.png') }}" alt="" role="presentation" loading="lazy">
        </div>
        <img class="hm-reels__deco hm-reels__deco--star1" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">
        <img class="hm-reels__deco hm-reels__deco--star2" src="{{ asset('assets/images/faq/star.png') }}" alt="" aria-hidden="true" loading="lazy">

        <div class="container hm-reels__container">

            {{-- Header --}}
            <div class="hm-reels__head">
                <span class="hm-reels__label hm-anim hm-anim--up">
                    <span class="hm-reels__label-icon" aria-hidden="true"></span>
                    <span class="hm-reels__label-text">{{ $csLabel ?? 'Our Journey' }}</span>
                </span>
                <h2 class="hm-reels__title hm-anim hm-anim--up hm-anim--d1" id="hmReelsTitle">{{ $csTitle ?? 'Watch Our Learning Journey' }}</h2>
                <p class="hm-reels__desc hm-anim hm-anim--up hm-anim--d2">
                    @isset($csDesc)
                        {{ $csDesc }}
                    @else
                        Catch the latest classroom moments, workshops, and success stories.<br>
                        Follow <a class="hm-reels__handle" href="https://www.instagram.com/hireminds_academy/" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i> @hireminds_academy</a> for more exclusive updates.
                    @endisset
                </p>
            </div>

            {{-- Swiper slider --}}
            <div class="swiper hm-reels__swiper hm-anim hm-anim--up hm-anim--d3">
                <div class="swiper-wrapper">
                    {{-- Rendered twice so there are more slides than are visible — this
                         gives the arrows / loop somewhere to advance to (5 images only). --}}
                    @foreach (array_merge($reels, $reels) as $reel)
                        <div class="swiper-slide hm-reels__slide">
                            <div class="hm-reel-float">
                                <div class="hm-reel" data-instagram="{{ $reel['url'] }}" role="link" tabindex="0"
                                     aria-label="Watch on Instagram: {{ $reel['title'] }}">
                                    <img class="hm-reel__img" src="{{ asset('assets/images/our-journey/'.$reel['image']) }}"
                                         alt="{{ $reel['title'] }}" loading="lazy">
                                    <span class="hm-reel__badge" aria-hidden="true"><i class="fa-brands fa-instagram"></i> Instagram Reel</span>
                                    <span class="hm-reel__overlay" aria-hidden="true"></span>
                                    <span class="hm-reel__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation --}}
            <div class="hm-reels__nav hm-anim hm-anim--up hm-anim--d4">
                <button type="button" class="hm-reels__navbtn hm-reels__navbtn--prev" id="hmReelsPrev" aria-label="Previous reels">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="hm-reels__navbtn hm-reels__navbtn--next" id="hmReelsNext" aria-label="Next reels">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </section>

@push('scripts')
    {{-- Reel slider — Swiper + click-to-open Instagram. Ships with the
         partial so the component works wherever it is included. --}}
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" crossorigin="anonymous" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var swiperEl = document.querySelector('.hm-reels__swiper');
            if (!swiperEl || typeof Swiper === 'undefined') return;

            new Swiper(swiperEl, {
                slidesPerView: 1.2,
                spaceBetween: 20,
                loop: true,
                speed: 1000,
                grabCursor: true,
                autoplay: { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
                navigation: { prevEl: '#hmReelsPrev', nextEl: '#hmReelsNext' },
                breakpoints: {
                    768:  { slidesPerView: 3, spaceBetween: 20 },
                    1200: { slidesPerView: 5, spaceBetween: 20 }
                }
            });

            // Whole card is clickable → open its Instagram Reel in a new tab.
            // Delegation covers Swiper's loop-cloned slides too.
            swiperEl.addEventListener('click', function (e) {
                var reel = e.target.closest('.hm-reel[data-instagram]');
                if (reel && reel.dataset.instagram) window.open(reel.dataset.instagram, '_blank', 'noopener');
            });
            swiperEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var reel = e.target.closest('.hm-reel[data-instagram]');
                if (reel && reel.dataset.instagram) { e.preventDefault(); window.open(reel.dataset.instagram, '_blank', 'noopener'); }
            });
        });
    </script>
@endpush
